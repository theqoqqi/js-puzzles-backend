<?
/**
 * @author: Qoqqi
 * 13.09.2022, 6:31
 */

namespace App\Core\Json;

class CodeFrame {

    public string $title;

    public string $description;

    public FileRange $visibleLines;

    public ?FileRange $editableLines;

    public ?FileRange $removedLines;

    public function __construct(array $json) {
        $this->title = $json['title'] ?? '';
        $this->description = $json['description'] ?? '';
        $this->visibleLines = FileRange::fromString($json['visibleLines']);
        $this->editableLines = isset($json['editableLines'])
            ? FileRange::fromString($json['editableLines'])
            : null;
        $this->removedLines = isset($json['removedLines'])
            ? FileRange::fromString($json['removedLines'])
            : null;
    }

    public static function fromJson(array $json): CodeFrame {
        return new CodeFrame($json);
    }
}
