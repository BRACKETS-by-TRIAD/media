<?php

namespace Brackets\Media\HasMedia;

use Brackets\Media\Exceptions\Collections\MediaCollectionAlreadyDefined;
use Brackets\Media\Exceptions\Collections\ThumbsDoesNotExists;
use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait as ParentHasMediaTrait;
use Spatie\MediaLibrary\Media as MediaModel;

/**
 * @property-read boolean $autoProcessMedia
 */
trait HasMediaCollectionsTrait {

    use ParentHasMediaTrait;
    // TODO after refactor, if the class is still too long, extract into sub-traits

    /**
     * Collection of Media Collections
     *
     * @var Collection
     */
    protected $mediaCollections;

    /**
     * Attaches and/or detaches all defined media collection to the model according to the $media
     *
     * TODO explaing what it does - specify the structure used, note that it does validation first and after everything passes, it executes attaching (or detaching) the media
     *
     * @param Collection $inputMedia
     */
    public function processMedia(Collection $inputMedia) {
//        Original structure
//        $request = [
//            'files' => [
//                [
//                    'collection' => 'documents',
//                    'name'       => 'test',
//                    'width'      => 200,
//                    'height'     => 200,
//                    'model'      => 'Brackets\Media\Test\TestModelWithCollections',
//                    'path'       => 'test.pdf',
//                ],
//            ],
//        ];

//        Changed structure
//        Don't we want to use maybe some class to represent the data structure?
//        Maybe what we want is a MediumOperation class, which holds {collection name, operation (detach, attach, replace), metadata, filepath)} what do you think?
//        $request = [
//            'documents' => [
//                [
//                    'id' => null,
//                    'collection_name' => 'documents',
//                    'model' => 'Brackets\Media\Test\TestModelWithCollections',
//                    'path' => 'test.pdf',
//                    'action' => 'add',
//                    'meta_data' => [
//                        'name' => 'test',
//                        'width' => 200,
//                        'height' => 200,
//                    ],
//                ],
//            ],
//        ];

        //First validate input
        $this->getMediaCollections()->each(function($mediaCollection) use ($inputMedia) {
             $this->validate(collect($inputMedia->get($mediaCollection->getName())), $mediaCollection);
        });

        //Then process each media
        $this->getMediaCollections()->each(function($mediaCollection) use ($inputMedia) {
            collect($inputMedia->get($mediaCollection->getName()))->each(function($inputMedium) use ($mediaCollection) {
                $this->processMedium($inputMedium, $mediaCollection);
            });
        });
    }

    public function processMedium($inputMedium, $mediaCollection)
    {
        //TODO refactore
        if (isset($inputMedium['id']) && $inputMedium['id']) {
            if($medium = app(MediaModel::class)->find($inputMedium['id'])) {
                if (isset($inputMedium['action']) && $inputMedium['action'] == 'delete') {
                    $medium->delete();
                } else {
                    $medium->customProperties = $inputMedium['meta_data'];
                    $medium->save();
                }
            }
        } else {

            // TODO this is really sick, it should be extracted in a method
            $metaData = [];
            if (isset($inputMedium['name'])) {
                $metaData['name'] = $inputMedium['name'];
            }
            if (isset($inputMedium['file_name'])) {
                $metaData['file_name'] = $inputMedium['file_name'];
            }
            if (isset($inputMedium['width'])) {
                $metaData['width'] = $inputMedium['width'];
            }
            if (isset($inputMedium['height'])) {
                $metaData['height'] = $inputMedium['height'];
            }

            // TODO extract "uploads" disk into config
            $inputMedium = Storage::disk('uploads')->getDriver()->getAdapter()->applyPathPrefix($inputMedium['path']);

            // TODO extract into validation phase
            $this->validateSizeAndTypeOfFile($inputMedium, $collection);

            $this->addMedia($inputMedium)
                ->withCustomProperties($metaData)
                ->toMediaCollection($collection->name, $collection->disk);
        }
    }

    public function validate(Collection $inputMediaForMediaCollection, MediaCollection $mediaCollection)
    {
        $this->validateCollectionMediaCount($inputMediaForMediaCollection, $mediaCollection);
        $inputMediaForMediaCollection->each(function($inputMedium) use ($mediaCollection) {
            $this->validateTypeOfFile($inputMedium, $mediaCollection);
            $this->validateSize($inputMedium, $mediaCollection);
        });
    }

    /**
     * Validate uploaded files count in collection
     *
     * @throws FileCannotBeAdded/TooManyFiles
     *
     */
    public function validateCollectionMediaCount(Collection $inputMediaForMediaCollection, MediaCollection $mediaCollection) {

        // TODO do we want to throw an exception? If you have limit only one file per media collection, don't you usually want to automatically replace the current file with the new one?
        if ($mediaCollection->getMaxNumberOfFiles()) {
            $alreadyUploadedMediaCount = $this->getMedia($mediaCollection->getName())->count();
            $forAddMediaCount = $inputMediaForMediaCollection->count(function($medium) { return $medium['action'] == 'add'; });
            $forDeleteMediaCount = $inputMediaForMediaCollection->count(function($medium) { return $medium['action'] == 'delete'; });
            $afterUploadCount = ($forAddMediaCount + $alreadyUploadedMediaCount - $forDeleteMediaCount);

            if ($afterUploadCount > $mediaCollection->getMaxNumberOfFiles()) {
                throw TooManyFiles::create($afterUploadCount, $mediaCollection->getMaxNumberOfFiles(), $mediaCollection->getName());
            }
        }
    }

    /**
     * Validate uploaded file mime type
     *
     * @throws FileCannotBeAdded/MimeTypeNotAllowed
     *
     */
    public function validateTypeOfFile($inputMedium, $mediaCollection) {
        if ($mediaCollection->getAcceptedFileTypes()) {
            $this->guardAgainstInvalidMimeType($inputMedium['path'], $mediaCollection->getAcceptedFileTypes());
        }
    }

    /**
     * Validate uploaded file size
     *
     * @throws FileCannotBeAdded/FileIsTooBig
     *
     */
    public function validateSize($inputMedium, $mediaCollection) {
        if ($mediaCollection->getMaxFileSize()) {
            $this->guardAgainstFileSizeLimit($inputMedium['path'], $mediaCollection->getMaxFileSize(), $mediaCollection->getName());
        }
    }

    /**
     * maybe this could be PR to spatie/laravel-medialibrary
     *
     * @param $filePath
     * @param $maxFileSize
     * @param $name
     * @throws FileIsTooBig
     */
    protected function guardAgainstFileSizeLimit($filePath, $maxFileSize, $name) {
        $validation = Validator::make(
            ['file' => new File($filePath)],
            ['file' => 'max:' . (round($maxFileSize / 1024))]
        );

        if ($validation->fails()) {
            throw FileIsTooBig::create($filePath, $maxFileSize, $name);
        }
    }

    /**
     * This hooks the initialization to the right place
     */
    protected function bootIfNotBooted() {
        parent::bootIfNotBooted();

        $this->initMediaCollections();
    }

    public static function bootHasMediaCollectionsTrait() {
        static::saving(function ($model) {
            /** @var self $model */
            if ($model->shouldAutoProcessMedia()) {
                $request = request();

                // FIXME what API should we expect? hard-coded files value or maybe according the collection name maybe?, so something like $model->processMedia(collect($request->only($model->getMediaCollections()->map->name)));
                if ($request->has('files')) {
                    $model->processMedia(collect($request->get('files')));
                }
            }
        });
    }

    // TODO maybe we want to add an option to globally turn off auto process for whole app?
    protected function shouldAutoProcessMedia() {
        if (property_exists($this, 'autoProcessMedia') && !!$this->autoProcessMedia) {
            return false;
        }

        return true;
    }

    protected function initMediaCollections() {
        $this->mediaCollections = collect();

        $this->registerMediaCollections();
    }

    /**
     * Register new Media Collection
     *
     * Adds new collection to model and set its name.
     *
     * @param $name
     * @return MediaCollection
     * @throws MediaCollectionAlreadyDefined
     */
    public function addMediaCollection($name): MediaCollection {
        if ($this->mediaCollections->has($name)) {
            throw new MediaCollectionAlreadyDefined;
        }

        $collection = MediaCollection::create($name);

        $this->mediaCollections->put($name, $collection);

        return $collection;
    }

    /**
     * Returns a collection of Media Collections
     *
     * @return Collection|MediaCollection[]
     */
    public function getMediaCollections(): Collection {
        return $this->mediaCollections;
    }

    /**
     * Returns a Media Collection according to the name
     *
     * If Media Collection was not registered on this model, null is returned
     *
     * @param $name
     * @return MediaCollection|null
     */
    public function getMediaCollection($name) : MediaCollection {
        return $this->mediaCollections->get($name);
    }

    // FIXME do we really want to have such filter? Anyone can filter it up very easily..
    public function getImageMediaCollections() {
        return $this->getMediaCollections()->filter->isImage();
    }

    // FIXME where this method should be?
    public function getThumbsForCollection(string $collectionName) {
        $collection = $this->getMediaCollection($collectionName);

        // FIXME why this does not check if the Media Collection has the conversion, it only checks if conversion exists in general?
        //FIXME: if image and thumb_200 doesnt exist throw exception to add thumb_200
        if ($this->hasMediaConversion('thumb_200')) {
            throw ThumbsDoesNotExists::thumbsConversionNotFound();
        }

        return $this->getMedia($collectionName)->map(function ($medium) use ($collection) {
            return [
                'id' => $medium->id,
                'url' => $medium->getUrl(),
                'thumb_url' => $collection->isImage() ? $medium->getUrl('thumb_200') : $medium->getUrl(),
                'type' => $medium->mime_type,
                'collection' => $collection->name,
                'name' => $medium->hasCustomProperty('name') ? $medium->getCustomProperty('name') : $medium->file_name,
                'size' => $medium->size
            ];
        });
    }

    //FIXME: this definitely shouldn't be here. Maybe it should not be anywhere :)
    public function registerComponentThumbs() {
        $this->getImageMediaCollections()->each(function ($collection) {
            $this->addMediaConversion('thumb_200')
                ->width(200)
                ->height(200)
                ->fit('crop', 200, 200)
                ->optimize()
                ->performOnCollections($collection->name);
        });
    }
}