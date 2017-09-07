<?php

namespace Brackets\Media\Test;

use Brackets\Media\HasMedia\HasMediaThumbsTrait;
use Illuminate\Database\Eloquent\Model;
// use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
// use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;

use Brackets\Media\HasMedia\HasMediaCollections;
use Brackets\Media\HasMedia\HasMediaCollectionsTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;

class TestModel extends Model implements HasMediaConversions, HasMediaCollections
{
    use HasMediaCollectionsTrait;
    use HasMediaThumbsTrait;

    protected $table = 'test_models';
    protected $guarded = [];
    public $timestamps = false;

    /**
     * Media collections
     *
     */
    public function registerMediaCollections() {
        
    }

    /**
     * Register the conversions that should be performed.
     *
     */
    public function registerMediaConversions(\Spatie\MediaLibrary\Media $media = null) {
        
    }
}