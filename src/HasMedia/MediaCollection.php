<?php

namespace Brackets\Media\HasMedia;

/**
 * @property-read string $name
 * @property-read string $disk
 * @property-read int $maxNumberOfFiles
 * @property-read int $maxFilesize
 * @property-read string $acceptedFileTypes
 * @property-read string $viewPermission
 * @property-read string $uploadPermission
 */

class MediaCollection {

	protected $name;
	protected $disk;
	protected $isImage = false;
	protected $maxNumberOfFiles;
	protected $maxFileSize;
	protected $acceptedFileTypes;
	protected $viewPermission;
	protected $uploadPermission;

	/**
	 * MediaCollection constructor.
	 *
	 * @param string $name
	 */
	public function __construct( string $name ) {
		$this->name = $name;
		$this->disk = config( 'media-collections.public_disk', 'media' );
	}

	/**
	 * @param string $name
	 *
	 * @return MediaCollection
	 */
	public static function create( string $name ): self {
		return new static( $name );
	}

	/**
	 * Specify a disk where to store this collection
	 *
	 * @param $disk
	 *
	 * @return $this
	 */
	public function disk( $disk ): self {
		$this->disk = $disk;

		return $this;
	}

	/**
	 * Alias to setting default private disk
	 *
	 * @return $this
	 */
	public function private(): self {
		$this->disk = config( 'media-collections.private_disk' );

		return $this;
	}

	/**
	 * Set the file count limit
	 *
	 * @param $maxNumberOfFiles
	 *
	 * @return $this
	 */
	public function maxNumberOfFiles( $maxNumberOfFiles ): self {
		$this->maxNumberOfFiles = $maxNumberOfFiles;

		return $this;
	}

	/**
	 * Set the file size limit
	 *
	 * @param $maxFileSize
	 *
	 * @return $this
	 */
	public function maxFileSize( $maxFileSize ): self {
		$this->maxFileSize = $maxFileSize;

		return $this;
	}

	/**
	 * Set the accepted file types (in MIME type format)
	 *
	 * @param array ...$acceptedFileTypes
	 *
	 * @return $this
	 */
	public function accepts( ...$acceptedFileTypes ): self {
		$this->acceptedFileTypes = $acceptedFileTypes;
		if ( collect( $this->acceptedFileTypes )->count() > 0 ) {
			$this->isImage = collect( $this->acceptedFileTypes )->reject( function ( $fileType ) {
					return substr( $fileType, 0, 5 ) === "image";
				} )->count() == 0;
		}

		return $this;
	}

	/**
	 * Set the ability (Gate) which is required to view the medium
	 *
	 * In most cases you would want to call private() to use default private disk.
	 *
	 * Otherwise, you may use other private disk for your own. Just be sure, your file is not accessible
	 *
	 * @param $viewPermission
	 *
	 * @return $this
	 */
	public function canView( $viewPermission ) {
		$this->viewPermission = $viewPermission;

		return $this;
	}

	/**
	 * Set the ability (Gate) which is required to upload & attach new files to the model
	 *
	 * @param $uploadPermission
	 *
	 * @return $this
	 */
	public function canUpload( $uploadPermission ) {
		$this->uploadPermission = $uploadPermission;

		return $this;
	}

	public function isImage() {
		return $this->isImage;
	}

	//FIXME: metoda disk by mohla mat druhy nepovinny paramater private, ktory len nastavi interny flag na true. Aby sme vedeli presnejsie ci ide o private alebo nie
	public function isPrivate() {
		return $this->disk == config( 'media-collections.private_disk' );
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getDisk() {
		return $this->disk;
	}

	/**
	 * @return mixed
	 */
	public function getMaxNumberOfFiles() {
		return $this->maxNumberOfFiles;
	}

	/**
	 * @return mixed
	 */
	public function getMaxFileSize() {
		return $this->maxFileSize;
	}

	/**
	 * @return mixed
	 */
	public function getAcceptedFileTypes() {
		return $this->acceptedFileTypes;
	}

	/**
	 * @return mixed
	 */
	public function getViewPermission() {
		return $this->viewPermission;
	}

	/**
	 * @return mixed
	 */
	public function getUploadPermission() {
		return $this->uploadPermission;
	}
}