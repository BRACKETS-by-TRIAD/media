<?php

namespace Brackets\Media\HasMedia;

use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\InvalidUploadPath;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read boolean $autoProcessMedia
 */
trait ProcessMediaTrait
{
    /**
     * Attaches and/or detaches all defined media collection to the model according to the $media
     *
     * This method process data from structure:
     *
     * $request = [
     *      ...
     *      'collectionName' => [
     *          [
     *              'id' => null,
     *              'collection_name' => 'collectionName',
     *              'path' => 'test.pdf',
     *              'action' => 'add',
     *              'meta_data' => [
     *                  'name' => 'test',
     *                  'width' => 200,
     *                  'height' => 200,
     *              ],
     *          ],
     *      ],
     *      ...
     * ];
     *
     * Firstly it validates input for max files count for mediaCollection, ile mimetype and file size, amd if the
     * validation passes it will add/change/delete media object to model
     *
     * @param Collection $inputMedia
     */
    public function processMedia(Collection $inputMedia): void
    {
//        Don't we want to use maybe some class to represent the data structure?
//        Maybe what we want is a MediumOperation class, which holds {collection name, operation (detach, attach, replace), metadata, filepath)} what do you think?

        //First validate input
        $this->getMediaCollections()->each(function ($mediaCollection) use ($inputMedia) {
            $this->validate(collect($inputMedia->get($mediaCollection->getName())), $mediaCollection);
        });

        //Then process each media
        $this->getMediaCollections()->each(function ($mediaCollection) use ($inputMedia) {
            collect($inputMedia->get($mediaCollection->getName()))->each(function ($inputMedium) use (
                $mediaCollection
            ) {
                $this->processMedium($inputMedium, $mediaCollection);
            });
        });
    }

    /**
     * Process single file metadata add/edit/delete to media library
     *
     * @param $inputMedium
     * @param $mediaCollection
     * @throws FileCannotBeAdded
     */
    public function processMedium(array $inputMedium, MediaCollection $mediaCollection): void
    {
        if (isset($inputMedium['id']) && $inputMedium['id']) {
            // Only media that already belongs to this model and this collection can be edited or deleted here,
            // a bare id must not be able to reach media of other models.
            if ($medium = $this->findOwnMedium($inputMedium['id'], $mediaCollection)) {
                if (isset($inputMedium['action']) && $inputMedium['action'] === 'delete') {
                    $medium->delete();
                } else {
                    $medium->custom_properties = $inputMedium['meta_data'] ?? [];
                    $medium->save();
                }
            }
        } elseif (isset($inputMedium['action']) && $inputMedium['action'] === 'add') {
            $mediumFileFullPath = $this->resolveUploadedFilePath($inputMedium['path'] ?? null);

            $this->addMedia($mediumFileFullPath)
                ->withCustomProperties($inputMedium['meta_data'] ?? [])
                ->toMediaCollection($mediaCollection->getName(), $mediaCollection->getDisk());
        }
    }

    /**
     * Find a medium by id, limited to the media attached to this model in the given collection
     *
     * @param mixed $id
     * @param MediaCollection $mediaCollection
     * @return Media|null
     */
    protected function findOwnMedium($id, MediaCollection $mediaCollection): ?Media
    {
        if (! $this->exists) {
            return null;
        }

        return $this->media()
            ->where('collection_name', $mediaCollection->getName())
            ->find($id);
    }

    /**
     * Resolve a path submitted by the client to an absolute path of a file inside the "uploads" disk.
     *
     * The submitted path is user input, so it is canonicalized (realpath, which also resolves symlinks)
     * and rejected unless it points to a regular file that stays inside the root of the uploads disk.
     *
     * @param mixed $path
     * @return string
     * @throws InvalidUploadPath
     */
    protected function resolveUploadedFilePath($path): string
    {
        if (! is_string($path) || $path === '' || str_contains($path, "\0")) {
            throw InvalidUploadPath::create(is_scalar($path) ? (string) $path : gettype($path));
        }

        $disk = Storage::disk('uploads');

        $root = realpath($disk->path(''));
        $fullPath = realpath($disk->path($path));

        if ($root === false || $fullPath === false || ! is_file($fullPath)) {
            throw InvalidUploadPath::create($path);
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (! str_starts_with($fullPath, $root)) {
            throw InvalidUploadPath::create($path);
        }

        return $fullPath;
    }

    /**
     * Validae input data for media
     *
     * @param Collection $inputMediaForMediaCollection
     * @param MediaCollection $mediaCollection
     * @throws FileCannotBeAdded
     */
    public function validate(Collection $inputMediaForMediaCollection, MediaCollection $mediaCollection): void
    {
        $this->validateCollectionMediaCount($inputMediaForMediaCollection, $mediaCollection);
        $inputMediaForMediaCollection->each(function ($inputMedium) use ($mediaCollection) {
            if (isset($inputMedium['action']) && $inputMedium['action'] === 'add') {
                $mediumFileFullPath = $this->resolveUploadedFilePath($inputMedium['path'] ?? null);
                $this->validateTypeOfFile($mediumFileFullPath, $mediaCollection);
                $this->validateSize($mediumFileFullPath, $mediaCollection);
            }
        });
    }

    /**
     * Validate uploaded files count in collection
     *
     * @param Collection $inputMediaForMediaCollection
     * @param MediaCollection $mediaCollection
     * @throws TooManyFiles
     */
    public function validateCollectionMediaCount(
        Collection $inputMediaForMediaCollection,
        MediaCollection $mediaCollection
    ): void {
        if ($mediaCollection->getMaxNumberOfFiles()) {
            $alreadyUploadedMediaCount = $this->getMedia($mediaCollection->getName())->count();
            $forAddMediaCount = $inputMediaForMediaCollection->filter(static function ($medium) {
                return isset($medium['action']) && $medium['action'] === 'add';
            })->count();
            $forDeleteMediaCount = $inputMediaForMediaCollection->filter(static function ($medium) {
                return isset($medium['action']) && $medium['action'] === 'delete';
            })->count();
            $afterUploadCount = ($forAddMediaCount + $alreadyUploadedMediaCount - $forDeleteMediaCount);

            if ($afterUploadCount > $mediaCollection->getMaxNumberOfFiles()) {
                throw TooManyFiles::create($mediaCollection->getMaxNumberOfFiles(), $mediaCollection->getName());
            }
        }
    }

    /**
     * Validate uploaded file mime type
     *
     * @param string $mediumFileFullPath
     * @param MediaCollection $mediaCollection
     */
    public function validateTypeOfFile(string $mediumFileFullPath, MediaCollection $mediaCollection): void
    {
        if ($mediaCollection->getAcceptedFileTypes()) {
            $this->guardAgainstInvalidMimeType($mediumFileFullPath, $mediaCollection->getAcceptedFileTypes());
        }
    }

    /**
     * Validate uploaded file size
     *
     * @param string $mediumFileFullPath
     * @param MediaCollection $mediaCollection
     * @throws FileIsTooBig
     */
    public function validateSize(string $mediumFileFullPath, MediaCollection $mediaCollection): void
    {
        if ($mediaCollection->getMaxFileSize()) {
            $this->guardAgainstFileSizeLimit(
                $mediumFileFullPath,
                $mediaCollection->getMaxFileSize(),
                $mediaCollection->getName()
            );
        }
    }

    /**
     * maybe this could be PR to spatie/laravel-medialibrary
     *
     * @param string $filePath
     * @param float $maxFileSize
     * @param string $name
     * @throws FileIsTooBig
     */
    protected function guardAgainstFileSizeLimit(string $filePath, float $maxFileSize, string $name): void
    {
        $validation = Validator::make(
            ['file' => new File($filePath)],
            ['file' => 'max:' . round($maxFileSize / 1024)]
        );

        if ($validation->fails()) {
            throw FileIsTooBig::create($filePath, $maxFileSize, $name);
        }
    }
}
