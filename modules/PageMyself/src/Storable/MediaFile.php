<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;

use function in_array;

/**
 * MediaFile
 * @property MediaFolder|null $mediaFolder
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
     * Get flat list of given media file/media folders
     * Does flatten if media folder is given, also is recursive
     * @param mixed $ids
     * @return MediaFile[]
     */
    public static function getFlatList(mixed $ids): array
    {
        if (!$ids) {
            return [];
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $storables = Storable::getByIds($ids);
        $files = [];
        if ($storables) {
            foreach ($storables as $storable) {
                if ($storable instanceof MediaFile) {
                    $files[$storable->id] = $storable;
                } elseif ($storable instanceof MediaFolder) {
                    $files = ArrayUtils::merge($files, $storable->getAllChildFiles());
                }
            }
        }
        return $files;
    }

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
    public function isVideo(): bool
    {
        return in_array($this->extension, ['mp4', 'webm']);
    }

    /**
     * Is image file that can be converted into thumbnails
     * @return bool
     */
    public function isImageWithThumbnails(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'gif', 'png', 'webp']);
    }

    /**
     * Is image file html valid, so it can be displayed in an <img> tag
     * @return bool
     */
    public function isImage(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'gif', 'png', 'webp', 'svg']);
    }

    /**
     * Get full name to this file
     * @return string
     */
    public function getFullName(): string
    {
        return $this->mediaFolder ? $this->mediaFolder->getFullName() . " / " . $this->filename : $this->filename;
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
     * @param int|null $thumbSize Only take effect when isImageWithThumbnails() is true
     * @return Url
     */
    public function getUrl(?int $thumbSize = null): Url
    {
        $file = $this->getPath();
        $basename = basename($file);
        $url = Url::getUrlToFile($file);
        if ($thumbSize && $this->isImageWithThumbnails()) {
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