<?php

namespace Brackets\Media\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class FileUploadController extends BaseController {

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function upload(Request $request) {
        $this->authorize('admin.upload');

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('', ['disk' => 'uploads']);
            return response()->json(['path' => $path], 200);
        }

        // FIXME use trans() to generate this message
        return response()->json('File was not provided', 422);
    }
}
