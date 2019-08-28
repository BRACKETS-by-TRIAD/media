<?php

namespace Brackets\Media\UrlGenerator;

use Spatie\MediaLibrary\Exceptions\UrlCannotBeDetermined;
use Spatie\MediaLibrary\UrlGenerator\LocalUrlGenerator as SpatieLocalUrlGenerator;

class LocalUrlGenerator extends SpatieLocalUrlGenerator
{
    /**
     * @throws UrlCannotBeDetermined
     * @return string
     */
    public function getUrl(): string
    {
        if ($this->media->disk === 'media_private') {
            $url = $this->getPathRelativeToRoot();

            return route('brackets/media::view', [], false) . '?path=' . $this->makeCompatibleForNonUnixHosts($url);
        } else {
            return parent::getUrl();
        }
    }
}
