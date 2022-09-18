<?
/**
 * @author: Qoqqi
 * 14.09.2022, 6:37
 */

namespace App\Core;

use App\Core\Json\CodeFrame;
use App\Core\Json\FileRange;
use App\Core\Json\PuzzleFileProps;
use function collect;
use function dd;

class WorkspaceFileProps {

    public string $file;

    /**
     * @var FileRange[]
     */
    public array $codeFrames;

    public function __construct(array $json) {
        $this->file = $json['file'];
        $this->codeFrames = collect($json['codeFrames'])
            ->map([FileRange::class, 'fromString'])
            ->toArray();
    }

    public static function fromProps(PuzzleFileProps $fileProps): WorkspaceFileProps {
        return self::fromJson([
            'file' => $fileProps->file,
            'codeFrames' => collect($fileProps->codeFrames)
                ->map(function (CodeFrame $codeFrame) {
                    return FileRange::toString($codeFrame->editableLines ?? FileRange::empty());
                })
                ->toArray(),
        ]);
    }

    public static function fromJson(array $json): WorkspaceFileProps {
        return new WorkspaceFileProps($json);
    }

    public static function toJson(WorkspaceFileProps $file): array {
        return [
            'file' => $file->file,
            'codeFrames' => collect($file->codeFrames)
                ->map([FileRange::class, 'toString'])
                ->toArray(),
        ];
    }
}
