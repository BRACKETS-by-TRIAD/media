<?php

namespace Brackets\Media\HasMedia;

use Exception;

/**
 * @property-read string $name
 * @property-read string $disk
 * @property-read int $maxNumberOfFiles
 * @property-read int $maxFilesize
 * @property-read string $acceptedFileTypes
 * @property-read string $viewPermission
 * @property-read string $uploadPermission
 */

class MediaCollection  {

    protected $name;
    protected $disk;
    protected $is_image = false;
    protected $maxNumberOfFiles;
    protected $maxFilesize;
    protected $acceptedFileTypes;
    protected $viewPermission;
    protected $uploadPermission;


    public function __construct(string $name) {
        $this->name = $name;
        $this->disk = config('media-collections.public_disk', 'media');
    }

    public function __get($property) {
        switch ($property) {
            case 'name':
                return $this->name;

            case 'disk':
                return $this->disk;

            case 'maxNumberOfFiles':
                return $this->maxNumberOfFiles;

            case 'maxFilesize':
                return $this->maxFilesize;

            case 'acceptedFileTypes':
                return $this->acceptedFileTypes;

            case 'viewPermission':
                return $this->viewPermission;

            case 'uploadPermission';
                return $this->uploadPermission;
        }

        throw new Exception("Property [".$property."] does not exist");   
    }

    public static function create(string $name) : self {
        return new static($name);
    }


    // TODO what is this for? because conversions can work also on non images
    /**
     * Set this collection contains an images. This allows the conversions functionality.
     *
     * @return $this
     */
    public function image() : self {
        $this->is_image = true;
        return $this;
    }

    /**
     * Specify a disk where to store this collection
     *
     * @param $disk
     * @return $this
     */
    public function disk($disk) : self {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Alias to setting default private disk
     *
     * @return $this
     */
    public function private() : self {
        $this->disk = config('media-collections.private_disk');
        return $this;
    }

    /**
     * Set the file count limit
     *
     * @param $maxNumberOfFiles
     * @return $this
     */
    public function maxNumberOfFiles($maxNumberOfFiles) : self {
        $this->maxNumberOfFiles = $maxNumberOfFiles;
        return $this;
    }

    /**
     * Set the file size limit
     *
     * @param $maxFilesize
     * @return $this
     */
    public function maxFilesize($maxFilesize) : self {
        $this->maxFilesize = $maxFilesize;
        return $this;
    }

    /**
     * Set the accepted file types (in MIME type format)
     *
     * @param array ...$acceptedFileTypes
     * @return $this
     */
    public function accepts(...$acceptedFileTypes) : self {
        $this->acceptedFileTypes = $acceptedFileTypes;
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
     * @return $this
     */
    public function canView($viewPermission) {
        $this->viewPermission = $viewPermission;
        return $this;
    }

    /**
     * Set the ability (Gate) which is required to upload & attach new files to the model
     *
     * @param $uploadPermission
     * @return $this
     */
    public function canUpload($uploadPermission) {
        $this->uploadPermission = $uploadPermission;
        return $this;
    }

    // TODO probably deprecated?
    public function isImage() {
        return $this->is_image;
    }

    // TODO probably deprecated?
    //FIXME: metoda disk by mohla mat druhy nepovinny paramater private, ktory len nastavi interny flag na true. Aby sme vedeli presnejsie ci ide o private alebo nie
    public function isPrivate() {
        return $this->disk == config('media-collections.private_disk');
    }
}