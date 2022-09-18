<?
/**
 * @author: Qoqqi
 * 13.09.2022, 6:28
 */

namespace App\Core\Json;

use function collect;

class Puzzle {

    public string $name;

    public string $appName;

    public string $title;

    public string $description;

    public string $mainPage;

    public array $variables;

    public array $json;

    /**
     * @var PuzzleFileProps[]
     */
    public array $files;

    public function __construct($json) {
        $this->json = $json;
        $this->name = $json['name'];
        $this->appName = $json['appName'];
        $this->title = $json['title'] ?? '';
        $this->description = $json['description'] ?? '';
        $this->mainPage = $json['mainPage'];
        $this->variables = $json['variables'];
        $this->files = collect($json['files'])
            ->map([PuzzleFileProps::class, 'fromJson'])
            ->toArray();
    }

    public static function fromJson(array $puzzleJson): Puzzle {
        return new Puzzle($puzzleJson);
    }
}
