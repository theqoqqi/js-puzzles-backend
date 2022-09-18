<?
/**
 * @author: Qoqqi
 * 14.09.2022, 6:23
 */

namespace App\Core;

use App\Core\Json\Puzzle;
use Illuminate\Support\Facades\Storage;
use function json_decode;

class Resources {

    public static function loadPuzzle(string $puzzleName): Puzzle {
        $json = self::getJson("puzzles/$puzzleName.json");

        return Puzzle::fromJson($json);
    }

    private static function getJson(string $path): array {
        $jsonText = Storage::get($path);

        return json_decode($jsonText, true);
    }
}
