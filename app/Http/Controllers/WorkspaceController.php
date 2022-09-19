<?php

namespace App\Http\Controllers;

use App\Core\ConfiguredPuzzle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use function response;

class WorkspaceController extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function setup(Request $request): JsonResponse {
        $userId = $this->getUserId($request);
        $puzzleName = $request->post('puzzle');

        $configuredPuzzle = ConfiguredPuzzle::setupFrom($puzzleName, $userId);

        return response()->json([
            'puzzle' => $configuredPuzzle->toJson(),
        ]);
    }

    public function load(Request $request): JsonResponse {
        $userId = $this->getUserId($request);
        $puzzleName = $request->post('puzzle');

        if (ConfiguredPuzzle::exists($puzzleName, $userId)) {
            $configuredPuzzle = ConfiguredPuzzle::loadFrom($puzzleName, $userId);
        } else {
            $configuredPuzzle = ConfiguredPuzzle::setupFrom($puzzleName, $userId);
        }

        return response()->json([
            'puzzle' => $configuredPuzzle->toJson(),
        ]);
    }

    public function getFile(Request $request): BinaryFileResponse {
        $userId = $this->getUserId($request);
        $file = $request->route()->parameter('file');

        $configuredPuzzle = ConfiguredPuzzle::fromWorkspace($userId);
        $path = $configuredPuzzle->getWorkspaceFilePath($file);

        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        $mimeType = $this->getMimeType($file);

        if ($mimeType) {
            $headers['Content-Type'] = $mimeType;
        }

        return response()->file(storage_path('app/' . $path), $headers);
    }

    public function saveCodeFrames(Request $request): JsonResponse {
        $userId = $this->getUserId($request);
        $configuredPuzzle = ConfiguredPuzzle::fromWorkspace($userId);

        $codeFrames = $request->post('codeFrames');

        foreach ($codeFrames as $codeFrame) {
            $file = $codeFrame['file'];
            $codeFrameIndex = $codeFrame['codeFrameIndex'];
            $newContents = $codeFrame['contents'];

            $configuredPuzzle->setCodeFrameContents($file, $codeFrameIndex, $newContents);
        }

        return response()->json([]);
    }

    public function saveCodeFrame(Request $request): JsonResponse {
        $userId = $this->getUserId($request);
        $configuredPuzzle = ConfiguredPuzzle::fromWorkspace($userId);

        $file = $request->route()->parameter('file');
        $newContents = $request->post('contents');
        $codeFrameIndex = $request->post('codeFrameIndex');

        $configuredPuzzle->setCodeFrameContents($file, $codeFrameIndex, $newContents);

        return response()->json([]);
    }

    private function getMimeType(string $file): ?string {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'js' => 'text/javascript',
            'html' => 'text/html',
            'css' => 'text/css',
        ];

        return $mimeTypes[$extension] ?? null;
    }
}
