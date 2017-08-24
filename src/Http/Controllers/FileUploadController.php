<?php

namespace Brackets\Media\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class FileUploadController extends BaseController {

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function upload(Request $request) {
        $this->authorize('admin.upload');

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('', ['disk' => 'uploads']);
            return response()->json(['path' => $path], 200);
        }

        return response()->json('File, model or collection is not provided', 422);
    }
}
