<?php

Route::middleware(['web'])->group(function () {
    Route::namespace('Brackets\Media\Http\Controllers')->group(function () {
        Route::post('upload',                   'FileUploadController@upload')->name('brackets/media:upload');
        Route::get('view',                      'FileViewController@view')->name('brackets/media:view');

//        Route::any('wysiwyg/drag-and-drop',          'Upload\WysiwygFileUploadController@wysiwygDragDropUpload')->name('brackets/media:wysiwyg.drag-and-drop');
//        Route::any('wysiwyg/upload',            'Upload\WysiwygFileUploadController@wysiwygImageUpload')->name('brackets/media:wysiwyg.upload');
    });
});