<?php

namespace Brackets\Media\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class FileUploadController extends BaseController {

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // protected $wysiwygUploadPath;

    public function __construct() {
        // $this->wysiwygUploadPath = '/uploads/wysiwyg';
    }

    public function upload(Request $request) {
        $this->authorize('admin.upload');

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('medialibray_temp_uploads');
            return response()->json(['path' => $path], 200);
        }

        return response()->json('File, model or collection is not provided', 422);
    }

    // public function wysiwygDragDropUpload(Request $request) {
    //     if (!$request->hasFile('upload')) {
    //         return array("uploaded" => 0, "error" => array("message" => "Failed to upload the file - file not sent"));
    //     }
    //     if (!$request->file('upload')->isValid()) {
    //         return array("uploaded" => 0, "error" => array("message" => "Failed to upload the file - file transfer failed"));
    //     }

    //     $destinationPath = public_path() . $this->wysiwygUploadPath;

    //     /**
    //      * @var \Symfony\Component\HttpFoundation\File\UploadedFile
    //      */
    //     $file = $request->file('upload');
    //     if(!$request->file('upload')->move($destinationPath, $file->getClientOriginalName())) {
    //         return array("uploaded" => 0, "error" => array("message" => "Failed to upload the file - could not move file"));
    //     }

    //     return array("uploaded" => 1, "fileName" => $file->getClientOriginalName(), "url" => $this->wysiwygUploadPath . "/" . $file->getClientOriginalName());
    // }

    // public function wysiwygImageUpload(Request $request) {
    //     $funcNum = $request->get("CKEditorFuncNum");
    //     $url = '';
    //     $message = '';
    //     $destinationPath = public_path() . $this->wysiwygUploadPath;

    //     if (!$request->hasFile('upload')) {
    //         $message = 'Failed to uplad - file not sent';
    //     }
    //     if (!$request->file('upload')->isValid()) {
    //         $message = 'Failed to uplad - file transfer failed';
    //     }

    //     $file = $request->file('upload');
    //     if($request->file('upload')->move($destinationPath, $file->getClientOriginalName())) {
    //         $url = $this->wysiwygUploadPath . "/" . $file->getClientOriginalName();
    //     }

    //     echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '$message');</script>";
    // }

}
