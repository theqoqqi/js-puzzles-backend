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
use function dd;
use function explode;
use function implode;
use function json_decode;
use function json_encode;
use function storage_path;
use const PHP_EOL;

class ConfiguredPuzzle {

    public Puzzle $puzzle;

    public int $userId;

    public function __construct(Puzzle $puzzle, int $userId) {
        $this->puzzle = $puzzle;
        $this->userId = $userId;
    }

    public function setup() {
        $this->setupWorkspace();
        $this->applyCodeFrames();
    }

    private function setupWorkspace(): void {
        $appPath = storage_path('app/' . $this->getAppPath());
        $workspacePath = storage_path('app/' . $this->getWorkspaceAppPath());

        Storage::deleteDirectory($this->getWorkspaceAppPath());
        File::copyDirectory($appPath, $workspacePath);

        $this->createWorkspaceConfig();
    }

    private function createWorkspaceConfig(): void {
        $configPath = $this->getWorkspaceConfigPath();
        $config = WorkspaceConfig::create($this->puzzle);

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

    private static function getPuzzleFromWorkspace(int $userId): Puzzle {
        $config = self::getWorkspaceConfig($userId);

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
        $configPath = self::getWorkspaceConfigPathForUserId($this->userId);
        $config = self::getWorkspaceConfig($this->userId);
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

    public static function setupFrom(string $puzzleName, int $userId): ConfiguredPuzzle {
        $puzzle = Resources::loadPuzzle($puzzleName);

        $configuredPuzzle = new ConfiguredPuzzle($puzzle, $userId);

        $configuredPuzzle->setup();

        return $configuredPuzzle;
    }

    public static function fromWorkspace(int $userId): ConfiguredPuzzle {
        $puzzle = self::getPuzzleFromWorkspace($userId);

        return new ConfiguredPuzzle($puzzle, $userId);
    }

    private static function getWorkspaceConfig(int $userId): WorkspaceConfig {
        $configPath = self::getWorkspaceConfigPathForUserId($userId);
        $configText = Storage::get($configPath);
        $configJson = json_decode($configText, true);

        return WorkspaceConfig::fromJson($configJson);
    }

    public function getAppPath(): string {
        return "apps/{$this->puzzle->appName}";
    }

    public function getWorkspaceConfigPath(): string {
        return "workspaces/$this->userId/config.json";
    }

    public function getWorkspaceAppPath(): string {
        return "workspaces/$this->userId/app";
    }

    public function getWorkspaceFilePath(string $file): string {
        return "workspaces/$this->userId/app/$file";
    }

    public static function getWorkspaceConfigPathForUserId($userId): string {
        return "workspaces/$userId/config.json";
    }
}
