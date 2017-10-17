<?php

namespace Brackets\Media\HasMedia;

use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;

interface HasMediaCollections extends HasMedia {

	/**
	 * @return array
	 */
	public function registerMediaCollections();

}