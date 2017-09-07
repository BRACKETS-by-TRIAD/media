<?php

namespace Brackets\Media\Test;

class TestModelWithCollections extends TestModel
{
    /**
     * Media collections
     *
     */
    public function registerMediaCollections() {

        $this->addMediaCollection('gallery')
             ->maxNumberOfFiles(20)
             ->maxFilesize(2*1024*1024)
             ->accepts('image/*');

        $this->addMediaCollection('documents')
             ->private()
             ->canView('vop.view')
             ->canUpload('vop.upload')
             ->maxNumberOfFiles(20)
             ->maxFilesize(2*1024*1024)
             ->accepts('application/pdf', 'application/msword');

        $this->addMediaCollection('zip')
            ->private()
            ->canView('vop.view')
            ->canUpload('vop.upload')
            ->maxNumberOfFiles(20)
            ->maxFilesize(2*1024*1024)
            ->accepts('application/octet-stream');
    }

    /**
     * Register the conversions that should be performed.
     *
     */
    public function registerMediaConversions(\Spatie\MediaLibrary\Media $media = null) {
        $this->autoRegisterThumb200();

        $this->addMediaConversion('thumb')
             ->width(368)
             ->height(232)
             ->sharpen(10)
             ->optimize()
             ->performOnCollections('gallery');
    }
}