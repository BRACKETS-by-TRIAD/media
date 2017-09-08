<?php

namespace Brackets\Media\Exceptions\FileCannotBeAdded;

use Spatie\MediaLibrary\Helpers\File;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;

class FileIsTooBig extends FileCannotBeAdded
{
    public static function create($file, $maxSize, $collectionName)
    {
        $actualFileSize = filesize($file);

        return new static(trans('brackets/media::media.exceptions.thumbs_does_not_exists', ['actualFileSize' => $actualFileSize, 'collectionName' => $collectionName, 'maxSize' => $maxSize]));
    }
}