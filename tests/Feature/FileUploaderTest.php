<?php namespace Brackets\Media\Test\Feature;

use Brackets\Media\Test\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;

use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\MimeTypeNotAllowed;
use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;

class FileUploaderTest extends TestCase
{

    /** @test */
    public function a_user_can_upload_file()
    {
        $this->withoutMiddleware();

        $data = [
            'name'      => 'test',
            'path'      => $this->getTestFilesDirectory('test.psd'),
        ];
        $file = new UploadedFile($data['path'], $data['name'], 'image/jpeg', filesize($data['path']) , null, true);

        $response = $this->call('POST', 'upload', $data, [], ['file' => $file], [], []);

        $response->assertSee('medialibray_temp_upload');
    }

}