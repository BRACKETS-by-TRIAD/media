<?php

namespace Brackets\Media\Exceptions\Collections;

use Exception;

class ThumbsDoesNotExists extends Exception
{
    public static function thumbsConversionNotFound()
    {
        return new static("Conversion with name thumb_200 not registered.");
    }
}