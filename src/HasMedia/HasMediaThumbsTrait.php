<?php

namespace Brackets\Media\HasMedia;

use Brackets\Media\Exceptions\Collections\MediaCollectionAlreadyDefined;
use Brackets\Media\Exceptions\Collections\ThumbsDoesNotExists;
use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\Conversion\ConversionCollection;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait as ParentHasMediaTrait;
use Spatie\MediaLibrary\Media as MediaModel;

/**
 * @property-read boolean $autoProcessMedia
 */
trait HasMediaThumbsTrait {

    public function getThumbs200ForCollection(string $mediaCollectionName) {
        $mediaCollection = $this->getMediaCollection($mediaCollectionName);

        return $this->getMedia($mediaCollectionName)->filter(function($medium) use ($mediaCollectionName) {
            return $conversions = ConversionCollection::createForMedia($medium)->filter(function($conversion) use ($mediaCollectionName) {
                    return $conversion->shouldBePerformedOn($mediaCollectionName);
                })->filter(function($conversion) {
                    return $conversion->getName() == 'thumb_200';
                })->count() > 0;
        })->map(function ($medium) use ($mediaCollection) {
            return [
                'id' => $medium->id,
                'url' => $medium->getUrl(),
                'thumb_url' => $mediaCollection->isImage() ? $medium->getUrl('thumb_200') : $medium->getUrl(),
                'type' => $medium->mime_type,
                'mediaCollection' => $mediaCollection->getName(),
                'name' => $medium->hasCustomProperty('name') ? $medium->getCustomProperty('name') : $medium->file_name,
                'size' => $medium->size
            ];
        });
    }

    public function autoRegisterThumb200() {
        $this->getMediaCollections()->filter->isImage()->each(function ($mediaCollection) {
            $this->addMediaConversion('thumb_200')
                ->width(200)
                ->height(200)
                ->fit('crop', 200, 200)
                ->optimize()
                ->performOnCollections($mediaCollection->getName());
        });
    }
}