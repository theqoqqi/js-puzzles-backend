<?
/**
 * @author: Qoqqi
 * 19.09.2022, 8:32
 */

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function json_decode;
use function response;

class PuzzlesController extends BaseController {

    public function getPuzzleList(Request $request): JsonResponse {
        $userId = $this->getUserId($request);
        $puzzleList = $this->getPuzzleListFor($userId);

        return response()->json([
            'puzzles' => $puzzleList,
        ]);
    }

    private function getPuzzleListFor(string $userId): array {
        $paths = Storage::allFiles('/puzzles');
        $puzzleList = [];

        foreach ($paths as $path) {
            $puzzleJson = json_decode(Storage::get($path), true);

            $puzzleList[] = [
                'name' => $puzzleJson['name'],
                'title' => $puzzleJson['title'],
                'description' => $puzzleJson['description'],
                'appName' => $puzzleJson['appName'],
            ];
        }

        return $puzzleList;
    }
}
