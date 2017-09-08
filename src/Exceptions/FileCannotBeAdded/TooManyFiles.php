<?php

namespace Brackets\Media\Exceptions\FileCannotBeAdded;

use Spatie\MediaLibrary\Helpers\File;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;

class TooManyFiles extends FileCannotBeAdded
{
    public static function create($maxFileCount, $collectionName)
    {
        return new static(trans('brackets/media::media.exceptions.too_many_files', ['collectionName' => $collectionName, 'maxFileCount' => $maxFileCount]));
    }
}