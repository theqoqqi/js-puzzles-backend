<?
/**
 * @author: Qoqqi
 * 19.09.2022, 8:32
 */

namespace App\Http\Controllers;

use App\Core\Resources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use function response;

class EditorController extends BaseController {

    public function loadPuzzle(Request $request): JsonResponse {
        $puzzleName = $request->route()->parameter('puzzle');
        $puzzleJson = Resources::loadPuzzleJson($puzzleName);

        return response()->json([
            'puzzle' => $puzzleJson,
        ]);
    }

    public function savePuzzle(Request $request): JsonResponse {
        $puzzleName = $request->route()->parameter('puzzle');
        $puzzleJson = $request->input('puzzle');

        Resources::savePuzzleJson($puzzleName, $puzzleJson);

        return response()->json([]);
    }
}
