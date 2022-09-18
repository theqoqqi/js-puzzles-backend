<?
/**
 * @author: Qoqqi
 * 14.09.2022, 5:45
 */

namespace App\Core\Json;

use function collect;

class PuzzleFileProps {

    public string $title;

    public string $description;

    public string $file;

    /**
     * @var CodeFrame[]
     */
    public array $codeFrames;

    public function __construct(array $json) {
        $this->title = $json['title'] ?? '';
        $this->description = $json['description'] ?? '';
        $this->file = $json['file'];
        $this->codeFrames = collect($json['codeFrames'])
            ->map([CodeFrame::class, 'fromJson'])
            ->toArray();
    }

    public static function fromJson(array $json): PuzzleFileProps {
        return new PuzzleFileProps($json);
    }
}
