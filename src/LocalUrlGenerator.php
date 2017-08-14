<?php

namespace Brackets\Media;

use Spatie\MediaLibrary\UrlGenerator\LocalUrlGenerator as SpatieLocalUrlGenerator;

class LocalUrlGenerator extends SpatieLocalUrlGenerator {

    public function getUrl(): string {
        if($this->media->disk == 'media-private') {
            $url = $this->getPathRelativeToRoot();
            return route('mediaLibrary.view', [], false) . '?path=' . $this->makeCompatibleForNonUnixHosts($url);
        } else {
            $url = $this->getBaseMediaDirectory().'/'.$this->getPathRelativeToRoot();
            return $this->makeCompatibleForNonUnixHosts($url);
        }
    }
}
