<?
/**
 * @author: Qoqqi
 * 14.09.2022, 6:06
 */

namespace App\Core;

use App\Core\Json\Puzzle;
use Illuminate\Support\Facades\Storage;
use function json_decode;
use function json_encode;

class WorkspaceConfig {

    public ?Puzzle $puzzle = null;

    public array $puzzles = [];

    public string $userId;

    public function __construct(string $userId) {
        $this->userId = $userId;
    }

    public function setPuzzleSolved(string $puzzle, bool $isSolved) {
        $this->puzzles[$puzzle] ??= [];
        $this->puzzles[$puzzle]['solved'] = $isSolved;
    }

    public function save() {
        $configPath = self::getWorkspaceConfigPathForUserId($this->userId);

        Storage::put($configPath, json_encode($this->toJson()));
    }

    private function initFromJson(array $json): void {
        $this->puzzle = $json['puzzle'] ? Resources::loadPuzzle($json['puzzle']) : null;
        $this->puzzles = $json['puzzles'] ?? [];
    }

    private function toJson(): array {
        return [
            'puzzle' => optional($this->puzzle)->name ?? null,
            'puzzles' => $this->puzzles,
        ];
    }

    private static function create($userId): WorkspaceConfig {
        return new WorkspaceConfig($userId);
    }

    private static function fromJson(array $configJson, string $userId): WorkspaceConfig {
        $config = new WorkspaceConfig($userId);
        $config->initFromJson($configJson);

        return $config;
    }

    public static function forUser(string $userId): WorkspaceConfig {
        $configPath = self::getWorkspaceConfigPathForUserId($userId);

        if (Storage::exists($configPath)) {
            $configText = Storage::get($configPath);
            $configJson = json_decode($configText, true);

            $config = self::fromJson($configJson, $userId);
        } else {
            $config = WorkspaceConfig::create($userId);
            $config->save();
        }

        return $config;
    }

    private static function getWorkspaceConfigPathForUserId(string $userId): string {
        return "workspaces/$userId/config.json";
    }
}
