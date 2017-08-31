<?php

namespace Brackets\Media\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Media as MediaModel;

class FileViewController extends BaseController {

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function view(Request $request) {
        $this->validate($request, [
            'path' => 'required|string'
        ]);

        list($fileId) = explode("/", $request->get('path'), 2);

        if ($medium = app(MediaModel::class)->find($fileId)) {
            if ($collection = $medium->model->getMediaCollection($medium->collection_name)) {

                if ($collection->viewPermission) {
                    $this->authorize($collection->viewPermission, [$medium->model]);
                }

                $storagePath = $request->get('path');
                $fileSystem = Storage::disk($collection->disk);

                if (!$fileSystem->has($storagePath)) {
                    abort(404);
                }

                return Response::make($fileSystem->get($storagePath), 200, [
                    'Content-Type' => $fileSystem->mimeType($storagePath),
                    'Content-Disposition' => 'inline; filename="' . basename($request->get('path')) . '"'
                ]);
            }
        }

        abort(404);
    }
}
