<?
/**
 * @author: Qoqqi
 * 13.09.2022, 19:48
 */

namespace App\Core;

use App\Core\Json\FileRange;
use App\Core\Json\Puzzle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function array_slice;
use function array_splice;
use function count;
use function define;
use function explode;
use function implode;
use function json_decode;
use function json_encode;
use function storage_path;

define('EOL', "\n");

class ConfiguredPuzzle {

    public Puzzle $puzzle;

    public string $userId;

    public function __construct(Puzzle $puzzle, string $userId) {
        $this->puzzle = $puzzle;
        $this->userId = $userId;
    }

    public function load() {
        $this->setupWorkspaceConfig();
    }

    public function setup() {
        $this->setupWorkspaceConfig();
        $this->setupPuzzleInstance();
        $this->setupCodeFrames();
    }

    private function setupPuzzleInstance(): void {
        $appPath = storage_path('app/' . $this->getAppPath());
        $workspacePath = storage_path('app/' . $this->getWorkspacePuzzleInstancePath());

        Storage::deleteDirectory($this->getWorkspacePuzzleInstancePath());
        File::copyDirectory($appPath, $workspacePath);

        $this->createPuzzleConfig();
    }

    private function setupWorkspaceConfig(): void {
        $config = WorkspaceConfig::forUser($this->userId);
        $config->puzzle = $this->puzzle;
        $config->save();
    }

    private function createPuzzleConfig(): void {
        $configPath = $this->getWorkspacePuzzleConfigPath();
        $config = PuzzleConfig::create($this->puzzle);

        Storage::put($configPath, json_encode($config->toJson()));
    }

    private function setupCodeFrames(): void {
        foreach ($this->puzzle->files as $puzzleFileProps) {
            $path = $this->getWorkspaceFilePath($puzzleFileProps->file);
            $contents = Storage::get($path);
            $lines = explode(EOL, $contents);

            foreach ($puzzleFileProps->codeFrames as $codeFrame) {
                $removedLines = $codeFrame->removedLines;

                if (!$removedLines->isEmpty()) {
                    for ($i = $removedLines->start; $i <= $removedLines->end; $i++) {
                        $lines[$i - 1] = '';
                    }
                }
            }

            Storage::put($path, implode(EOL, $lines));
        }
    }

    private static function getPuzzleFromWorkspace(string $userId): Puzzle {
        $config = WorkspaceConfig::forUser($userId);

        return $config->puzzle;
    }

    public function toJson(): array {
        $config = self::getPuzzleConfig($this->puzzle->name, $this->userId);
        $json = $this->puzzle->json;

        foreach ($this->puzzle->files as $fileIndex => $puzzleFileProps) {
            foreach ($puzzleFileProps->codeFrames as $codeFrameIndex => $codeFrame) {
                $editedRange = $config->files[$fileIndex]->codeFrames[$codeFrameIndex];
                $framesJson = $json['files'][$fileIndex]['codeFrames'];
                $fixedFrames = $this->fixCodeFramesInJson($framesJson, $codeFrameIndex, $editedRange);
                $json['files'][$fileIndex]['codeFrames'] = $fixedFrames;
                $fixedVisibleLines = FileRange::fromString($fixedFrames[$codeFrameIndex]['visibleLines'] ?? '0-0');
                $contents = $this->getFrameContents($puzzleFileProps->file, $fixedVisibleLines);

                $newCodeFrameJson = $fixedFrames[$codeFrameIndex];
                $newCodeFrameJson['contents'] = $contents;

                $json['files'][$fileIndex]['codeFrames'][$codeFrameIndex] = $newCodeFrameJson;
            }
        }

        return $json;
    }

    private function fixCodeFramesInJson(array $framesJson, int $frameIndex, FileRange $editedLines): array {
        $codeFrameJson = $framesJson[$frameIndex];
        $editableLines = FileRange::fromString($codeFrameJson['editableLines'] ?? '0-0');
        $delta = $editedLines->getLength() - $editableLines->getLength();

        if ($delta === 0) {
            return $framesJson;
        }

        $visibleLines = FileRange::fromString($codeFrameJson['visibleLines'] ?? '0-0');

        $visibleLines->modifySize($delta);
        $editableLines->modifySize($delta);

        $framesJson[$frameIndex]['visibleLines'] = FileRange::toString($visibleLines);
        $framesJson[$frameIndex]['editableLines'] = FileRange::toString($editableLines);

        $frameCount = count($framesJson);

        for ($i = $frameIndex + 1; $i < $frameCount; $i++) {
            $visibleLines = FileRange::fromString($framesJson[$i]['visibleLines'] ?? '0-0');
            $editableLines = FileRange::fromString($framesJson[$i]['editableLines'] ?? '0-0');
            $removedLines = FileRange::fromString($framesJson[$i]['removedLines'] ?? '0-0');

            if (!$visibleLines->isEmpty()) {
                $visibleLines->move($delta);
            }
            if (!$editableLines->isEmpty()) {
                $editableLines->move($delta);
            }
            if (!$removedLines->isEmpty()) {
                $removedLines->move($delta);
            }

            $framesJson[$i]['visibleLines'] = FileRange::toString($visibleLines);
            $framesJson[$i]['editableLines'] = FileRange::toString($editableLines);
            $framesJson[$i]['removedLines'] = FileRange::toString($removedLines);
        }

        return $framesJson;
    }

    private function getFrameContents(string $file, FileRange $range): string {
        if ($range->isEmpty()) {
            return '';
        }

        $path = $this->getWorkspaceFilePath($file);
        $contents = Storage::get($path);
        $lines = explode(EOL, $contents);
        $start = $range->start;
        $length = $range->end - $start + 1;
        $visibleLines = array_slice($lines, $start - 1, $length);

        return implode(EOL, $visibleLines);
    }

    public function setCodeFrameContents(string $file, int $codeFrameIndex, string $newFrameContents) {
        $configPath = self::getWorkspacePuzzleConfigPathFor($this->puzzle->name, $this->userId);
        $config = self::getPuzzleConfig($this->puzzle->name, $this->userId);
        $fileProps = $config->getFileProps($file);
        $range = $fileProps->getCodeFrameRange($codeFrameIndex);
        $path = $this->getWorkspaceFilePath($file);

        $oldFrameContents = Storage::get($path);
        $oldFrameLines = explode(EOL, $oldFrameContents);
        $newFrameLines = explode(EOL, $newFrameContents);

        $start = $range->start;
        $length = $range->end - $start + 1;

        $newFileLines = $oldFrameLines;
        array_splice($newFileLines, $start - 1, $length, $newFrameLines);
        $newFileContents = implode(EOL, $newFileLines);

        $fileProps->resizeCodeFrame($codeFrameIndex, count($newFrameLines));

        Storage::put($path, $newFileContents);
        Storage::put($configPath, json_encode($config->toJson()));
    }

    public static function exists(string $puzzleName, string $userId): bool {
        $puzzleConfigPath = self::getWorkspacePuzzleConfigPathFor($puzzleName, $userId);

        return Storage::exists($puzzleConfigPath);
    }

    public static function loadFrom(string $puzzleName, string $userId): ConfiguredPuzzle {
        $puzzle = Resources::loadPuzzle($puzzleName);

        $configuredPuzzle = new ConfiguredPuzzle($puzzle, $userId);

        $configuredPuzzle->load();

        return $configuredPuzzle;
    }

    public static function setupFrom(string $puzzleName, string $userId): ConfiguredPuzzle {
        $puzzle = Resources::loadPuzzle($puzzleName);

        $configuredPuzzle = new ConfiguredPuzzle($puzzle, $userId);

        $configuredPuzzle->setup();

        return $configuredPuzzle;
    }

    public static function fromWorkspace(string $userId): ConfiguredPuzzle {
        $puzzle = self::getPuzzleFromWorkspace($userId);

        return new ConfiguredPuzzle($puzzle, $userId);
    }

    private static function getPuzzleConfig(string $puzzleName, string $userId): PuzzleConfig {
        $configPath = self::getWorkspacePuzzleConfigPathFor($puzzleName, $userId);
        $configText = Storage::get($configPath);
        $configJson = json_decode($configText, true);

        return PuzzleConfig::fromJson($configJson);
    }

    public function getAppPath(): string {
        return "apps/{$this->puzzle->appName}";
    }

    public function getWorkspaceFilePath(string $file): string {
        return $this->getWorkspacePuzzleInstancePath() . '/' . $file;
    }

    public function getWorkspacePuzzleInstancePath(): string {
        return "workspaces/$this->userId/puzzles/{$this->puzzle->name}/app";
    }

    public function getWorkspacePuzzleConfigPath(): string {
        return self::getWorkspacePuzzleConfigPathFor($this->puzzle->name, $this->userId);
    }

    public static function getWorkspacePuzzleConfigPathFor(string $puzzleName, string $userId): string {
        return "workspaces/$userId/puzzles/{$puzzleName}/config.json";
    }
}
