<?php

namespace Brackets\Media\Exceptions\FileCannotBeAdded;

use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class InvalidUploadPath extends FileCannotBeAdded
{
    /**
     * @param string $path
     * @return InvalidUploadPath
     */
    public static function create(string $path): InvalidUploadPath
    {
        return new static(trans('brackets/media::media.exceptions.invalid_upload_path', [
            'path' => $path,
        ]));
    }
}
