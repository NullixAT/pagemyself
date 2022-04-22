<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Storable\StorableFile;
use function in_array;

/**
 * MediaFile
 * @property mixed|null $tags
 * @property mixed|null $metadata
 */
class MediaFile extends StorableFile
{
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
}