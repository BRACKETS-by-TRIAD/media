<?php

namespace Brackets\Media\HasMedia;

use Brackets\Media\Exceptions\Collections\MediaCollectionAlreadyDefined;
use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait as ParentHasMediaTrait;
use Spatie\MediaLibrary\Media as MediaModel;

/**
 * @property-read boolean $autoProcessMedia
 */
trait HasMediaCollectionsTrait {

	use ParentHasMediaTrait;

	protected $autoProcessMedia = true;

	/**
	 * Collection of Media Collections
	 *
	 * @var Collection
	 */
	protected $mediaCollections;

	/**
	 * Attaches and/or detaches all defined media collection to the model according to the $media
	 *
	 * This method proccess data from structure:
	 *
	 * $request = [
	 *      ...
	 *      'collectionName' => [
	 *          [
	 *              'id' => null,
	 *              'collection_name' => 'collectionName',
	 *              'path' => 'test.pdf',
	 *              'action' => 'add',
	 *              'meta_data' => [
	 *                  'name' => 'test',
	 *                  'width' => 200,
	 *                  'height' => 200,
	 *              ],
	 *          ],
	 *      ],
	 *      ...
	 * ];
	 *
	 * Firstly it validates input for max files count for mediaCollection, ile mimetype and file size, amd if the
	 * validation passes it will add/change/delete media object to model
	 *
	 * @param Collection $inputMedia
	 */
	public function processMedia( Collection $inputMedia ) {
//        Don't we want to use maybe some class to represent the data structure?
//        Maybe what we want is a MediumOperation class, which holds {collection name, operation (detach, attach, replace), metadata, filepath)} what do you think?

		//First validate input
//        dd($inputMedia);
		$this->getMediaCollections()->each( function ( $mediaCollection ) use ( $inputMedia ) {
			$this->validate( collect( $inputMedia->get( $mediaCollection->getName() ) ), $mediaCollection );
		} );

		//Then process each media
		$this->getMediaCollections()->each( function ( $mediaCollection ) use ( $inputMedia ) {
			collect( $inputMedia->get( $mediaCollection->getName() ) )->each( function ( $inputMedium ) use (
				$mediaCollection
			) {
				$this->processMedium( $inputMedium, $mediaCollection );
			} );
		} );
	}

	/**
	 * Process single file metadata add/edit/delete to media library
	 *
	 * @param $inputMedium
	 * @param $mediaCollection
	 */
	public function processMedium( $inputMedium, $mediaCollection ) {
		if ( isset( $inputMedium['id'] ) && $inputMedium['id'] ) {
			if ( $medium = app( MediaModel::class )->find( $inputMedium['id'] ) ) {
				if ( isset( $inputMedium['action'] ) && $inputMedium['action'] == 'delete' ) {
					$medium->delete();
				} else {
					$medium->customProperties = $inputMedium['meta_data'];
					$medium->save();
				}
			}
		} else if ( isset( $inputMedium['action'] ) && $inputMedium['action'] == 'add' ) {
			$mediumFileFullPath = Storage::disk( 'uploads' )->getDriver()->getAdapter()->applyPathPrefix( $inputMedium['path'] );

			$this->addMedia( $mediumFileFullPath )
			     ->withCustomProperties( $inputMedium['meta_data'] )
			     ->toMediaCollection( $mediaCollection->getName(), $mediaCollection->getDisk() );
		}
	}

	/**
	 * Validae input data for media
	 *
	 * @param Collection $inputMediaForMediaCollection
	 * @param MediaCollection $mediaCollection
	 */
	public function validate( Collection $inputMediaForMediaCollection, MediaCollection $mediaCollection ) {
		$this->validateCollectionMediaCount( $inputMediaForMediaCollection, $mediaCollection );
		$inputMediaForMediaCollection->each( function ( $inputMedium ) use ( $mediaCollection ) {
			if ( $inputMedium['action'] == 'add' ) {
				$mediumFileFullPath = Storage::disk( 'uploads' )->getDriver()->getAdapter()->applyPathPrefix( $inputMedium['path'] );
				$this->validateTypeOfFile( $mediumFileFullPath, $mediaCollection );
				$this->validateSize( $mediumFileFullPath, $mediaCollection );
			}
		} );
	}

	/**
	 * Validate uploaded files count in collection
	 *
	 * @throws FileCannotBeAdded/TooManyFiles
	 *
	 */
	public function validateCollectionMediaCount(
		Collection $inputMediaForMediaCollection,
		MediaCollection $mediaCollection
	) {

		if ( $mediaCollection->getMaxNumberOfFiles() ) {
			$alreadyUploadedMediaCount = $this->getMedia( $mediaCollection->getName() )->count();
			$forAddMediaCount          = $inputMediaForMediaCollection->filter( function ( $medium ) {
				return $medium['action'] == 'add';
			} )->count();
			$forDeleteMediaCount       = $inputMediaForMediaCollection->filter( function ( $medium ) {
				return $medium['action'] == 'delete' ? 1 : 0;
			} )->count();
			$afterUploadCount          = ( $forAddMediaCount + $alreadyUploadedMediaCount - $forDeleteMediaCount );

			if ( $afterUploadCount > $mediaCollection->getMaxNumberOfFiles() ) {
				throw TooManyFiles::create( $mediaCollection->getMaxNumberOfFiles(), $mediaCollection->getName() );
			}
		}
	}

	/**
	 * Validate uploaded file mime type
	 *
	 * @throws FileCannotBeAdded/MimeTypeNotAllowed
	 *
	 */
	public function validateTypeOfFile( $mediumFileFullPath, $mediaCollection ) {
		if ( $mediaCollection->getAcceptedFileTypes() ) {
			$this->guardAgainstInvalidMimeType( $mediumFileFullPath, $mediaCollection->getAcceptedFileTypes() );
		}
	}

	/**
	 * Validate uploaded file size
	 *
	 * @throws FileCannotBeAdded/FileIsTooBig
	 *
	 */
	public function validateSize( $mediumFileFullPath, $mediaCollection ) {
		if ( $mediaCollection->getMaxFileSize() ) {
			$this->guardAgainstFileSizeLimit( $mediumFileFullPath, $mediaCollection->getMaxFileSize(),
				$mediaCollection->getName() );
		}
	}

	/**
	 * maybe this could be PR to spatie/laravel-medialibrary
	 *
	 * @param $filePath
	 * @param $maxFileSize
	 * @param $name
	 *
	 * @throws FileIsTooBig
	 */
	protected function guardAgainstFileSizeLimit( $filePath, $maxFileSize, $name ) {
		$validation = Validator::make(
			[ 'file' => new File( $filePath ) ],
			[ 'file' => 'max:' . ( round( $maxFileSize / 1024 ) ) ]
		);

		if ( $validation->fails() ) {
			throw FileIsTooBig::create( $filePath, $maxFileSize, $name );
		}
	}

	/**
	 * This hooks the initialization to the right place
	 */
	protected function bootIfNotBooted() {
		parent::bootIfNotBooted();

		$this->initMediaCollections();
	}

	public static function bootHasMediaCollectionsTrait() {
		static::saving( function ( $model ) {
			/** @var self $model */
			if ( $model->shouldAutoProcessMedia() ) {
				$model->processMedia( collect( request()->only( $model->getMediaCollections()->map->getName()->toArray() ) ) );
			}
		} );
	}

	protected function shouldAutoProcessMedia() {
		if ( config( 'media-collections.auto_process' ) && property_exists( $this,
				'autoProcessMedia' ) && ! ! $this->autoProcessMedia ) {
			return true;
		}

		return false;
	}

	protected function initMediaCollections() {
		$this->mediaCollections = collect();

		$this->registerMediaCollections();
	}

	/**
	 * Register new Media Collection
	 *
	 * Adds new collection to model and set its name.
	 *
	 * @param $name
	 *
	 * @return MediaCollection
	 * @throws MediaCollectionAlreadyDefined
	 */
	public function addMediaCollection( $name ): MediaCollection {
		if ( $this->mediaCollections->has( $name ) ) {
			throw new MediaCollectionAlreadyDefined;
		}

		$collection = MediaCollection::create( $name );

		$this->mediaCollections->put( $name, $collection );

		return $collection;
	}

	/**
	 * Returns a collection of Media Collections
	 *
	 * @return Collection|MediaCollection[]
	 */
	public function getMediaCollections(): Collection {
		return $this->mediaCollections;
	}

	/**
	 * Returns a Media Collection according to the name
	 *
	 * If Media Collection was not registered on this model, null is returned
	 *
	 * @param $name
	 *
	 * @return MediaCollection|null
	 */
	public function getMediaCollection( $name ): MediaCollection {
		return $this->mediaCollections->get( $name );
	}
}