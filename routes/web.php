<?php

Route::middleware(['web'])->group(function () {
    Route::namespace('Brackets\Media\Http\Controllers')->group(function () {
        Route::post('upload',                   'FileUploadController@upload')->name('brackets/media::upload');
        Route::get('view',                      'FileViewController@view')->name('brackets/media::view');
    });
});