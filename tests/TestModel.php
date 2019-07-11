<?php

namespace Brackets\Media\Test;

use Brackets\Media\HasMedia\HasMediaThumbsTrait;
use Illuminate\Database\Eloquent\Model;
// use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
// use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;

use Brackets\Media\HasMedia\HasMediaCollectionsTrait;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\Models\Media;


class TestModel extends Model implements HasMedia
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
     * @param Media|null $media
     */
    public function registerMediaConversions(Media $media = null) {
        
    }
}
