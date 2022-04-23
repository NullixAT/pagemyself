<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;

use function in_array;

/**
 * MediaFile
 * @property mixed|null $tags
 * @property mixed|null $metadata
 */
class MediaFile extends StorableFile
{
    /**
     * All available thumb sizes
     * @var int[]
     */
    public static array $thumbSizes = [
        100,
        500,
        1000,
        1500,
        1920
    ];

    /**
     * Folder
     * @var string|null
     */
    public ?string $folder = __DIR__ . "/../../public/uploads";

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }

    /**
     * Is video file that can be viewed in the browser
     * @return bool
     */
    public function isVideoFile(): bool
    {
        return in_array($this->extension, ['mp4', 'webm']);
    }

    /**
     * Is image file that can be viewed in the browser
     * @return bool
     */
    public function isImageFile(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'gif', 'png', 'webp']);
    }

    /**
     * Get thumbnail path
     * @param int $thumbSize
     * @return Url
     */
    public function getThumbPath(int $thumbSize): string
    {
        $file = $this->getPath();
        $basename = basename($file);
        return dirname($file) . "/t-" . $thumbSize . "-" . $basename;
    }

    /**
     * Get public url to this file
     * @param int|null $thumbSize
     * @return Url
     */
    public function getUrl(?int $thumbSize = null): Url
    {
        $file = $this->getPath();
        $basename = basename($file);
        $url = Url::getUrlToFile($file);
        if ($thumbSize) {
            $file = $this->getThumbPath($thumbSize);
            $url->setPath(str_replace("/" . $basename, "/" . basename($file), $url->getPath()));
        }
        return $url;
    }

    /**
     * Store with given file
     * If UploadedFile is given, it does MOVE the file, not COPY it
     * @param UploadedFile|string|null $file String is considered as binary filedata
     */
    public function store(UploadedFile|string|null $file = null): void
    {
        // delete thumbs on update
        if ($this->id && $file) {
            foreach (self::$thumbSizes as $thumbSize) {
                $thumbPath = $this->getThumbPath($thumbSize);
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }
        }
        parent::store($file);
    }


    /**
     * Delete
     * @param bool $force
     * @return void
     */
    public function delete(bool $force = false): void
    {
        foreach (self::$thumbSizes as $thumbSize) {
            $thumbPath = $this->getThumbPath($thumbSize);
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
        parent::delete($force);
    }


}