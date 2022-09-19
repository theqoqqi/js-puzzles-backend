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
use function explode;
use function implode;
use function json_decode;
use function json_encode;
use function storage_path;
use const PHP_EOL;

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
        $this->setupPuzzleInstance();
    }

    private function setupPuzzleInstance(): void {
        $appPath = storage_path('app/' . $this->getAppPath());
        $workspacePath = storage_path('app/' . $this->getWorkspacePuzzleInstancePath());

        Storage::deleteDirectory($this->getWorkspacePuzzleInstancePath());
        File::copyDirectory($appPath, $workspacePath);

        $this->createPuzzleConfig();
        $this->applyCodeFrames();
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

    private function applyCodeFrames(): void {
        foreach ($this->puzzle->files as $puzzleFileProps) {
            $path = $this->getWorkspaceFilePath($puzzleFileProps->file);
            $contents = Storage::get($path);
            $lines = explode(PHP_EOL, $contents);

            foreach ($puzzleFileProps->codeFrames as $codeFrame) {
                $removedLines = $codeFrame->removedLines;

                if ($removedLines) {
                    for ($i = $removedLines->start; $i <= $removedLines->end; $i++) {
                        $lines[$i - 1] = '';
                    }
                }
            }

            Storage::put($path, implode(PHP_EOL, $lines));
        }
    }

    private static function getPuzzleFromWorkspace(string $userId): Puzzle {
        $config = WorkspaceConfig::forUser($userId);

        return $config->puzzle;
    }

    public function toJson(): array {
        $json = $this->puzzle->json;

        foreach ($this->puzzle->files as $fileIndex => $puzzleFileProps) {
            foreach ($puzzleFileProps->codeFrames as $codeFrameIndex => $codeFrame) {
                $contents = $this->getFrameContents($puzzleFileProps->file, $codeFrame->visibleLines);

                $json['files'][$fileIndex]['codeFrames'][$codeFrameIndex]['contents'] = $contents;
            }
        }

        return $json;
    }

    private function getFrameContents(string $file, FileRange $range): string {
        $path = $this->getWorkspaceFilePath($file);
        $contents = Storage::get($path);
        $lines = explode(PHP_EOL, $contents);
        $start = $range->start;
        $length = $range->end - $start + 1;
        $visibleLines = array_slice($lines, $start - 1, $length);

        return implode(PHP_EOL, $visibleLines);
    }

    public function setCodeFrameContents(string $file, int $codeFrameIndex, string $frameContents) {
        $configPath = self::getWorkspacePuzzleConfigPathFor($this->puzzle->name, $this->userId);
        $config = self::getPuzzleConfig($this->puzzle->name, $this->userId);
        $fileProps = $config->getFileProps($file);
        $range = $fileProps->codeFrames[$codeFrameIndex];
        $path = $this->getWorkspaceFilePath($file);

        $oldContents = Storage::get($path);
        $oldLines = explode(PHP_EOL, $oldContents);
        $frameLines = explode(PHP_EOL, $frameContents);

        $start = $range->start;
        $length = $range->end - $start + 1;

        $newLines = $oldLines;
        array_splice($newLines, $start - 1, $length, $frameLines);
        $newContents = implode(PHP_EOL, $newLines);

        $range->end -= $length;
        $range->end += count($frameLines);

        Storage::put($path, $newContents);
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
        $configuredPuzzle = self::loadFrom($puzzleName, $userId);

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
