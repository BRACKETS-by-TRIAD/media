<?php

namespace Brackets\Media\HasMedia;

use Spatie\MediaLibrary\HasMedia\HasMediaTrait as ParentHasMediaTrait;

trait HasMediaCollectionsTrait
{
    use ParentHasMediaTrait;

    /**
     * This hooks the initialization to the right place
     */
    protected function bootIfNotBooted()
    {
        parent::bootIfNotBooted();

        $this->initMediaCollections();
    }


    /**
     * Initialize all collections for model
     */
    protected function initMediaCollections(): void
    {
        $this->mediaCollections = [];

        $this->registerMediaCollections();
    }

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
