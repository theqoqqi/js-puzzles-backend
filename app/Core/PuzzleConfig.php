<?
/**
 * @author: Qoqqi
 * 14.09.2022, 6:06
 */

namespace App\Core;

use App\Core\Json\Puzzle;
use function collect;

class PuzzleConfig {

    public Puzzle $puzzle;

    /**
     * @var WorkspaceFileProps[]
     */
    public array $files;

    private function initFromPuzzle(Puzzle $puzzle) {
        $this->puzzle = $puzzle;
        $this->files = collect($puzzle->files)
            ->map([WorkspaceFileProps::class, 'fromProps'])
            ->toArray();
    }

    private function initFromJson(array $json): void {
        $this->puzzle = Resources::loadPuzzle($json['puzzle']);
        $this->files = collect($json['files'])
            ->map([WorkspaceFileProps::class, 'fromJson'])
            ->toArray();
    }

    public function toJson(): array {
        return [
            'puzzle' => $this->puzzle->name,
            'files' => collect($this->files)
                ->map([WorkspaceFileProps::class, 'toJson'])
                ->toArray(),
        ];
    }

    public function getFileProps(string $path): WorkspaceFileProps {
        return collect($this->files)->firstWhere('file', $path);
    }

    public static function create(Puzzle $puzzle): PuzzleConfig {
        $config = new PuzzleConfig();
        $config->initFromPuzzle($puzzle);

        return $config;
    }

    public static function fromJson(array $configJson): PuzzleConfig {
        $config = new PuzzleConfig();
        $config->initFromJson($configJson);

        return $config;
    }
}
