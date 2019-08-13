<?php

namespace Brackets\Media\HasMedia;

use Spatie\MediaLibrary\HasMedia\HasMediaTrait as ParentHasMediaTrait;

trait HasMediaCollectionsTrait
{
    use ParentHasMediaTrait;

    /**
     * Register new Media Collection
     *
     * Adds new collection to model and set its name.
     *
     * @param $name
     *
     * @return MediaCollection
     */
    public function addMediaCollection($name): MediaCollection
    {
        $mediaCollection = MediaCollection::create($name);

        $this->mediaCollections[] = $mediaCollection;

        return $mediaCollection;
    }
}
