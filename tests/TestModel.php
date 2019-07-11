<?php

namespace Brackets\Media\Test;

use Brackets\Media\HasMedia\HasMediaCollectionsTrait;
use Brackets\Media\HasMedia\HasMediaThumbsTrait;
use Brackets\Media\HasMedia\ProcessMediaTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\Models\Media;

class TestModel extends Model implements HasMedia
{
    use HasMediaCollectionsTrait;
    use HasMediaThumbsTrait;
    use ProcessMediaTrait;

    protected $table = 'test_models';
    protected $guarded = [];
    public $timestamps = false;

    /**
     * Media collections
     *
     */
    public function registerMediaCollections()
    {
    }

    /**
     * Register the conversions that should be performed.
     *
     * @param Media|null $media
     */
    public function registerMediaConversions(Media $media = null)
    {
    }
}
