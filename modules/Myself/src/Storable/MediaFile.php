<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Gumlet\ImageResize;

use function array_reverse;
use function file_exists;
use function htmlentities;
use function implode;
use function in_array;
use function strlen;
use function substr;
use function unlink;

/**
 * MediaFile
 * @property MediaFileFolder|null $mediaFileFolder
 * @property string|null $title
 * @property mixed|null $metadata
 */
class MediaFile extends StorableFile
{
    public const THUMBNAIL_SIZES = [
        self::THUMBNAIL_SIZE_SMALL => [100, 100],
        self::THUMBNAIL_SIZE_MEDIUM => [300, 300],
        self::THUMBNAIL_SIZE_LARGE => [1000, 1000],
        self::THUMBNAIL_SIZE_HD => [1920, 1920]
    ];

    public const THUMBNAIL_SIZE_SMALL = 1;
    public const THUMBNAIL_SIZE_MEDIUM = 2;
    public const THUMBNAIL_SIZE_LARGE = 3;
    public const THUMBNAIL_SIZE_HD = 4;

    /**
     * Folder
     * @var string|null
     */
    protected ?string $folder = __DIR__ . "/../../public/uploads";

    /**
     * Get a flat list of all given files and folders recursively with all childs
     * @param MediaFile[]|MediaFileFolder[]|Storable[] $storables
     * @return MediaFile[]
     */
    public static function getFlatListOfFilesRecursive(array $storables): array
    {
        $files = [];
        foreach ($storables as $storable) {
            if ($storable instanceof MediaFile) {
                $files[$storable->id] = $storable;
            } elseif ($storable instanceof MediaFileFolder) {
                $files = ArrayUtils::merge($files, $storable->getAllChildFiles());
            }
        }
        return $files;
    }

    /**
     * Get a flat list of all image data from original with thumbs for given files and folders recursively with all childs
     * @param MediaFile[]|MediaFileFolder[]|Storable[] $storables
     * @return array
     */
    public static function getFlatListOfImageDataRecursive(array $storables): array
    {
        $files = self::getFlatListOfFilesRecursive($storables);
        $imageData = [];
        foreach ($files as $file) {
            $arr = $file->getImageData();
            if ($arr) {
                $imageData[$file->id] = $arr;
            }
        }
        return $imageData;
    }

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        $selfStorableSchema->properties['title']->databaseType = 'text';
    }

    /**
     * Get image data for all thumbs and original
     * @return array|null
     */
    public function getImageData(): ?array
    {
        if (!$this->getPath() || !$this->isImageFile() || !isset($this->metadata['imageDimensions']['original'])) {
            return null;
        }
        $imageData = [];
        $imageData['id'] = $this->id;
        $imageData['title'] = $this->title ?? $this->filename;
        foreach (MediaFile::THUMBNAIL_SIZES as $size => $row) {
            $thumbPath = $this->getThumbPath($size);
            if (!$thumbPath) {
                continue;
            }
            $imageData['sizes']['thumb-' . $size]['thumbSize'] = $size;
            $imageData['sizes']['thumb-' . $size]['url'] = Url::getUrlToFile($thumbPath);
            $imageData['sizes']['thumb-' . $size]['dimensions'] = $this->metadata['imageDimensions'][$size] ?? null;
        }
        $imageData['sizes']['original']['size'] = Url::getUrlToFile($this->getPath());
        $imageData['sizes']['original']['url'] = Url::getUrlToFile($this->getPath());
        $imageData['sizes']['original']['dimensions'] = $this->metadata['imageDimensions']['original'];
        return $imageData;
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
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        $downloadUrl = $this->getDownloadUrl();
        if (!$downloadUrl) {
            return '';
        }
        return '<a href="' . $downloadUrl . '" title="__download_file__" class="myself-tag" title="' . Lang::get(
                '__download_file__'
            ) . ": " . $this->filename . '"><span class="material-icons">download</span></a>';
    }

    /**
     * Get path to the thumb file with given size
     * @param int $size The thumb size self::THUMBNAIL_
     * @param bool $fileCheck If true, then does return the path only if the file really exists
     * @return string|null Null if fileCheck is enabled an file do not exist on disk
     */
    public function getThumbPath(int $size, bool $fileCheck = true): ?string
    {
        $path = $this->getPath($fileCheck);
        if (!$path) {
            return null;
        }
        $path = substr($path, 0, -strlen($this->extension) - 1) . "-thumb-$size." . $this->extension;
        if ($fileCheck && !file_exists($path)) {
            return null;
        }
        return $path;
    }

    /**
     * Get smallest thumb path when file is an image
     * If no thumbnail exist, it return original path
     * @return string|null
     */
    public function getSmallestThumbPath(): ?string
    {
        if (!$this->isImageFile()) {
            return null;
        }
        foreach (self::THUMBNAIL_SIZES as $size => $row) {
            $path = $this->getThumbPath($size);
            if ($path) {
                return $path;
            }
        }
        return $this->getPath();
    }

    /**
     * Get biggest thumb path when file is an image
     * If no thumbnail exist, it return original path
     * @param int|null $maxSize Max thumb size
     * @return string|null
     */
    public function getBiggestThumbPath(?int $maxSize = null): ?string
    {
        if (!$this->isImageFile()) {
            return null;
        }
        foreach (array_reverse(self::THUMBNAIL_SIZES, true) as $size => $row) {
            if ($maxSize !== null && $maxSize > $size) {
                continue;
            }
            $path = $this->getThumbPath($size);
            if ($path) {
                return $path;
            }
        }
        return $this->getPath();
    }

    /**
     * Get biggest thumb url when file is an image
     * If no thumbnail exist, it return original path
     * @param int|null $maxSize Max thumb size
     * @return Url|null
     */
    public function getBiggestThumbUrl(?int $maxSize = null): ?Url
    {
        $path = $this->getBiggestThumbPath($maxSize);
        if ($path) {
            return Url::getUrlToFile($path);
        }
        return null;
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
     * Get lazy load container to display image/video only when container is visible
     * @return string
     */
    public function getLazyLoadContainer(): string
    {
        $sources = [];
        $metadata = $this->metadata;
        if (isset($metadata['imageDimensions'])) {
            foreach (MediaFile::THUMBNAIL_SIZES as $size => $row) {
                $path = $this->getThumbPath($size);
                if ($path) {
                    $dimensions = $metadata['imageDimensions'][$size] ?? null;
                    $imgUrl = Url::getUrlToFile($path);
                    if ($dimensions) {
                        $sources[] = $dimensions['w'] . '|' . $dimensions['h'] . '|' . $imgUrl;
                    } else {
                        $sources[] = $imgUrl;
                    }
                }
            }
            if (isset($metadata['imageDimensions']['original'])) {
                $sources[] = $metadata['imageDimensions']['original']['w'] . "|"
                    . $metadata['imageDimensions']['original']['h'] . "|"
                    . Url::getUrlToFile($this->getPath());
            } else {
                $sources[] = Url::getUrlToFile($this->getPath());
            }
        }
        return '<div class="myself-lazy-load" data-img="' . implode(";", $sources) . '"
                         data-alt="' . htmlentities($backgroundImage->title ?? $this->filename) . '"></div>';
    }

    /**
     * Store with given file
     * If UploadedFile is given, it does MOVE the file, not COPY it
     * @param UploadedFile|string|null $file String is considered as binary filedata
     */
    public function store(UploadedFile|string|null $file = null): void
    {
        $existingThumbPaths = [];
        $sizes = self::THUMBNAIL_SIZES;
        $sizes = array_reverse($sizes, true);
        if ($this->id) {
            foreach ($sizes as $size => $row) {
                $thumbPath = $this->getThumbPath($size);
                if ($thumbPath) {
                    $existingThumbPaths[$size] = $thumbPath;
                }
            }
        }
        parent::store($file);
        // create thumbnails for images
        if ($file && in_array($this->extension, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
            require_once __DIR__ . "/../../vendor/php-image-resize/lib/ImageResize.php";
            require_once __DIR__ . "/../../vendor/php-image-resize/lib/ImageResizeException.php";
            $metadata = $this->metadata;
            $image = new ImageResize($this->getPath());
            if (!isset($metadata['imageDimensions']['original'])) {
                $metadata['imageDimensions']['original'] = [
                    'w' => $image->getSourceWidth(),
                    'h' => $image->getSourceHeight()
                ];
            }
            foreach ($sizes as $size => $row) {
                if ($metadata['imageDimensions']['original']['w'] > $row[0] || $metadata['imageDimensions']['original']['h'] > $row[1]) {
                    $thumbPath = $this->getThumbPath($size, false);
                    $image->resizeToBestFit($row[0], $row[1]);
                    $image->save($thumbPath);
                    unset($existingThumbPaths[$size]);
                    $image = new ImageResize($thumbPath);
                    $metadata['imageDimensions'][$size] = [
                        'w' => $image->getDestWidth(),
                        'h' => $image->getDestHeight()
                    ];
                }
            }
            if ($this->metadata !== $metadata) {
                $this->metadata = $metadata;
                $this->store();
            }
        }
        if ($file) {
            // remove old thumb files
            foreach ($existingThumbPaths as $existingThumbPath) {
                unlink($existingThumbPath);
            }
        }
    }

    /**
     * Delete
     * @param bool $force
     * @return void
     */
    public function delete(bool $force = false): void
    {
        foreach (self::THUMBNAIL_SIZES as $size => $row) {
            $thumbPath = $this->getThumbPath($size);
            if ($thumbPath) {
                unlink($thumbPath);
            }
        }
        parent::delete($force);
    }


}