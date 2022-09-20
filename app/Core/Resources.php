<?
/**
 * @author: Qoqqi
 * 14.09.2022, 6:23
 */

namespace App\Core;

use App\Core\Json\Puzzle;
use Illuminate\Support\Facades\Storage;
use function json_decode;
use function json_encode;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Resources {

    public static function loadPuzzle(string $puzzleName): Puzzle {
        $json = self::loadPuzzleJson($puzzleName);

        return Puzzle::fromJson($json);
    }

    public static function loadPuzzleJson(string $puzzleName): array {
        return self::getJson("puzzles/$puzzleName.json");
    }

    public static function savePuzzleJson(string $puzzleName, $json): void {
        self::saveJson("puzzles/$puzzleName.json", $json);
    }

    public static function saveJson(string $path, $json): void {
        $jsonText = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        Storage::put($path, $jsonText);
    }

    public static function getJson(string $path): array {
        $jsonText = Storage::get($path);

        return json_decode($jsonText, true);
    }
}
