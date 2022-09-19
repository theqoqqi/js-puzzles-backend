<?
/**
 * @author: Qoqqi
 * 13.09.2022, 6:35
 */

namespace App\Core\Json;

use function explode;

class FileRange {

    public int $start;

    public int $end;

    public function __construct($start, $end) {
        $this->start = $start;
        $this->end = $end;
    }

    public function isEmpty(): bool {
        return $this->start === 0 && $this->end === 0;
    }

    public function modifySize(int $modifyBy): int {
        return $this->resize($this->getLength() + $modifyBy);
    }

    public function resize(int $newLength): int {
        $oldLength = $this->getLength();

        $this->end -= $oldLength;
        $this->end += $newLength;

        return $newLength - $oldLength;
    }

    public function move(int $delta): void {
        $this->start += $delta;
        $this->end += $delta;
    }

    public function getLength(): int {
        return $this->end - $this->start + 1;
    }

    public static function fromString(string $range = null): FileRange {
        $parts = explode('-', $range);

        return new FileRange($parts[0], $parts[1] ?? $parts[0]);
    }

    public static function toString(FileRange $fileRange): string {
        return "$fileRange->start-$fileRange->end";
    }

    public static function empty(): FileRange {
        return new FileRange(0, 0);
    }
}
