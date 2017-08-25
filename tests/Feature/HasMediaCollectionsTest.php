<?php namespace Brackets\Media\Test\Feature;

use Brackets\Media\Test\TestCase;
use Brackets\Media\Test\TestModelWithCollections;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;

use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\MimeTypeNotAllowed;
use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;

class HasMediaCollectionsTest extends TestCase
{

    /** @test */
    public function empty_collection_returns_a_laravel_collection()
    {
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $this->testModel->getMediaCollections());
    }

    /** @test */
    public function not_empty_collection_returns_a_laravel_collection()
    {
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $this->testModelWithCollections->getMediaCollections());
    }

    /** @test */
    public function check_media_collections_count()
    {
        $this->assertCount(0, $this->testModel->getMediaCollections());
        $this->assertCount(3, $this->testModelWithCollections->getMediaCollections());
    }

    /** @test */
    public function check_image_media_collections_count () {
        $this->assertCount(0, $this->testModel->getImageMediaCollections());
        $this->assertCount(1, $this->testModelWithCollections->getImageMediaCollections());
    }

    /** @test */
    public function user_can_register_new_file_collection_and_upload_files()
    {
        $this->testModel->addMediaCollection('documents')
                        ->title('Documents');

        $this->assertCount(1, $this->testModel->getMediaCollections());
        $this->assertCount(0, $this->testModel->getMedia());

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.pdf'
                ],
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.docx'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();

        $this->assertCount(2, $this->testModel->getMedia('documents'));
        $firstMedia = $this->testModel->getMedia('documents')->first();
        $this->assertStringStartsWith('application/pdf', $firstMedia->mime_type);
    }

    /** @test */
    public function model_is_saved_automatically_when_model_is_saved() {
        // TODO
        $this->assertTrue(true);
    }

    /** @test */
    public function model_is_not_saved_automatically_while_model_is_saved_if_this_feature_is_disabled() {
        // TODO
        $this->assertTrue(true);
    }

    /** @test */
    public function user_cannot_upload_not_allowed_file_types() {
        $this->expectException(MimeTypeNotAllowed::class);

        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        ->accepts('application/pdf, application/msword');

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.psd'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();

        $this->assertCount(0, $this->testModel->getMedia('documents'));
    }

     public function multiple_allowed_mime_types_can_be_defined() {
        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        // FIXME better API would be to use PHP7 ... option to allow multiple values
                        ->accepts('application/pdf, application/msword');

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.pdf'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();

        $this->assertCount(1, $this->testModel->getMedia('documents'));
    }

    /** @test */
    public function user_cannot_upload_more_files_than_allowed() {
        $this->expectException(TooManyFiles::class);

        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        ->maxNumberOfFiles(2);

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.psd'
                ],
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.txt'
                ],
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.docx'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();

        $this->assertCount(0, $this->testModel->getMedia('documents'));
    }

    /** @test */
    public function user_cannot_upload_more_files_than_is_allowed_in_multiple_requests() {
        $this->expectException(TooManyFiles::class);

        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        ->maxNumberOfFiles(2);

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.psd'
                ],
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.txt'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();
        // let's be sure we arranged this test correctly (so this is not a real test assertion)
        $this->assertCount(0, $this->testModel->getMediaCollections());

        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        ->maxNumberOfFiles(2);

        $request2 = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.docx'
                ],
            ]
        ]);

        $this->testModel->processMedia(collect($request2->get('files')));
        $this->testModel = $this->testModel->fresh();

        // finally we can assert
        $this->assertCount(2, $this->testModel->getMedia('documents'));
        // TODO let's double-check that original two documents are attached (and not replaced by new one)
    }

    /** @test */ // FIXME this one is redundant, we already tested that in previous test, I think we can totally delete this one
    public function user_can_upload_exact_number_of_defined_files() {
        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        ->maxNumberOfFiles(2);

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.psd'
                ],
                 [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.docx'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();

        $this->assertCount(2, $this->testModel->getMedia('documents'));
    }

    /** @test */
    public function user_cannot_upload_file_exceeding_max_file_size() {
        $this->expectException(FileIsTooBig::class);

        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        ->maxFilesize(100*1024); //100kb


        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.psd'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();

        $this->assertCount(0, $this->testModel->getMedia('documents'));
    }

    /** @test */
    public function user_can_upload_files_in_max_file_size() {
        $this->testModel->addMediaCollection('documents')
                        ->title('Documents')
                        ->maxFilesize(1*1024); //1kb

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.txt'
                ]
            ]
        ]);

        $this->testModel->processMedia(collect($request->get('files')));
        $this->testModel = $this->testModel->fresh();

        $this->assertCount(1, $this->testModel->getMedia('documents'));
    }

    /** @test */
    public function not_authorized_user_can_get_public_media()
    {
        $this->assertCount(0, $this->testModelWithCollections->getMedia('gallery'));

        $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'gallery',
                    'name'       => 'test',
                    'width'      => 200,
                    'height'     => 200,
                    'model'      => 'Brackets\Media\Test\TestModelWithCollections',
                    'path'       => 'test.jpg'
                ]
            ]
        ]);

        $this->testModelWithCollections->processMedia(collect($request->get('files')));
        $this->testModelWithCollections = $this->testModelWithCollections->fresh();

        $media = $this->testModelWithCollections->getMedia('gallery');

        $this->assertCount(1, $media);

        $response = $this->call('GET', $media->first()->getUrl());

        // let's assert that the access was not forbidden (but as long as we don't have a real nginx serving the file, we cannot actually get the file
        $this->assertNotEquals(403, $response->getStatusCode());
        // that's why we at least check if the final URL is correct
        // TODO
    }

    /** @test */
    public function not_authorized_user_cannot_get_protected_media()
    {
        $this->assertCount(0, $this->testModelWithCollections->getMedia('documents'));

         $request = $this->getRequest([
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'width'      => 200,
                    'height'     => 200,
                    'model'      => 'Brackets\Media\Test\TestModelWithCollections',
                    'path'       => 'test.pdf'
                ]
            ]
        ]);

        $this->testModelWithCollections->processMedia(collect($request->get('files')));
        $this->testModelWithCollections = $this->testModelWithCollections->fresh();

        $media = $this->testModelWithCollections->getMedia('documents');

        $this->assertCount(1, $media);

        $response = $this->call('GET', $media->first()->getUrl());

        $response->assertStatus(403);
    }

    /** @test */
    public function should_save_model_with_in_auto_process() {
        $response = $this->post('/test-model/create', [
            'name' => 'Test small file',
            'files' => [
                [
                    'collection' => 'documents',
                    'name'       => 'test',
                    'width'      => 200,
                    'height'     => 200,
                    'model'      => 'Brackets\Media\Test\TestModelWithCollections',
                    'path'       => 'test.pdf'
                ]
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas($this->testModelWithCollections->getTable(), [ 'id' => 2, 'name' => 'Test small file', 'width' => null ]);
    }

    /** @test */
    public function should_not_save_model_if_media_failed_in_auto_process() {
        $response = $this->post('/test-model/create', [
            'name' => 'Test big file',
            'files' => [
                [
                    'collection' => 'zip',
                    'name'       => 'test',
                    'model'      => 'Brackets\Media\Test\TestModel',
                    'path'       => 'test.zip'
                ]
            ],
        ]);

        $response->assertStatus(500);

        $this->assertDatabaseMissing($this->testModelWithCollections->getTable(), [ 'id' => 1, 'name' => 'Test big file', 'width' => null ]);
    }

    private function getRequest($data) { 
        return Request::create('test', 'GET', $data);        
    }

}