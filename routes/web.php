<?php

Route::middleware(['web'])->group(function () {
    Route::namespace('Brackets\Media\Http\Controllers')->group(function () {
        //TODO change names of the routes with package prepend and slash style
        Route::post('upload',                   'FileUploadController@upload')->name('mediaLibrary.upload');
        Route::get('view',                      'FileViewController@view')->name('mediaLibrary.view');

//        Route::any('wysiwyg/dragdrop',          'Upload\UploadController@wysiwygDragDropUpload')->name('mediaLibrary.wysiwyg.dragdrop');
//        Route::any('wysiwyg/upload',            'Upload\UploadController@wysiwygImageUpload')->name('mediaLibrary.wysiwyg.upload');
    });
});