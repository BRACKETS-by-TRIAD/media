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

    /**
     * Collection of Media Collections
     *
     * @var Collection
     */
    protected $mediaCollections;

    /**
     * Attach all defined media collection to the model
     *
     * TODO explaing what it does - specify the structure used, note that it does validation first and after everything passes, it executes attaching (or detaching) the media
     *
     * @param Collection $files
     */
    public function processMedia(Collection $files) {

        // TODO do we want to use this proprietary structure $files? Don't we want to use maybe some class to represent the data structure?

        $mediaCollections = $this->getMediaCollections();

        // TODO why this is not done per collection basis? What is the reason?
        $this->validateCollectionMediaCount($files);

        // TODO we should first validate EVERYHTING and once validated we execute stuff

        $files->each(function ($file) use ($mediaCollections) {
            $collection = $mediaCollections->get($file['collection']);

            if (is_null($collection)) {
                // TODO what do we do? do we just skip?
            }

            if (isset($file['id']) && $file['id']) {
                if (isset($file['deleted']) && $file['deleted']) {
                    if ($medium = app(MediaModel::class)->find($file['id'])) {
                        $medium->delete();
                    }
                } /* else {
                    TODO update meta data? - PPE: What was meant with this TODO? I have no idea
                }*/
            } else {

                // TODO this is really sick, it should be extracted in a method
                $metaData = [];
                if (isset($file['name'])) {
                    $metaData['name'] = $file['name'];
                }
                if (isset($file['file_name'])) {
                    $metaData['file_name'] = $file['file_name'];
                }
                if (isset($file['width'])) {
                    $metaData['width'] = $file['width'];
                }
                if (isset($file['height'])) {
                    $metaData['height'] = $file['height'];
                }

                // TODO extract "uploads" disk into config
                $file = Storage::disk('uploads')->getDriver()->getAdapter()->applyPathPrefix($file['path']);

                // TODO extract into validation phase
                $this->validateSizeAndTypeOfFile($file, $collection);

                $this->addMedia($file)
                    ->withCustomProperties($metaData)
                    ->toMediaCollection($collection->name, $collection->disk);
            }
        });
    }

    /**
     * Validate uploaded files count in collection
     *
     * @throws FileCannotBeAdded/TooManyFiles
     *
     */
    public function validateCollectionMediaCount(Collection $files) {

        //FIXME refactor

        // TODO do we want to throw an exception? If you have limit only one file per media collection, don't you usually want to automatically replace the current file with the new one?

        $files->groupBy('collection')->each(function ($collectionMedia, $collectionName) {
            $collection = $this->getMediaCollection($collectionName);

            if ($collection->maxNumberOfFiles) {
                $alreadyUploadedCollectionMedia = $this->getMedia($collectionName)->count();

                if (($collectionMedia->count() + $alreadyUploadedCollectionMedia) > $collection->maxNumberOfFiles) {
                    throw TooManyFiles::create(($collectionMedia->count() + $alreadyUploadedCollectionMedia), $collection->maxNumberOfFiles, $collection->name);
                }
            }
        });
    }

    /**
     * Validate uploaded files mime type and size
     *
     * @throws FileCannotBeAdded/MimeTypeNotAllowed
     * @throws FileCannotBeAdded/FileIsTooBig
     *
     */
    public function validateSizeAndTypeOfFile($filePath, $mediaCollection) {
        if ($mediaCollection->acceptedFileTypes) {
            $this->guardAgainstInvalidMimeType($filePath, $mediaCollection->acceptedFileTypes);
        }

        if ($mediaCollection->maxFilesize) {
            $this->guardAgainstFilesizeLimit($filePath, $mediaCollection->maxFilesize, $mediaCollection->name);
        }
    }

    // maybe this could be PR to spatie/laravel-medialibrary
    protected function guardAgainstFilesizeLimit($filePath, $maxFilesize, $name) {
        $validation = Validator::make(
            ['file' => new File($filePath)],
            ['file' => 'max:' . (round($maxFilesize / 1024))]
        );

        if ($validation->fails()) {
            throw FileIsTooBig::create($filePath, $maxFilesize, $name);
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
     * @param $name
     * @return MediaCollection
     * @throws MediaCollectionAlreadyDefined
     */
    public function addMediaCollection($name): MediaCollection {
        // FIXME cover this condition in tests
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

    //FIXME: this definitely shouldn't be here
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