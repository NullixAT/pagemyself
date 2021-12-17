<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Utils\ArrayUtils;

/**
 * MediaFileFolder
 * @property MediaFileFolder|null $parent
 * @property string $name
 */
class MediaFileFolder extends StorableExtended
{
    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }

    /**
     * Get all child files
     * @return MediaFile[]
     */
    public function getAllChildFiles(): array
    {
        $childs = ArrayUtils::merge(
            MediaFile::getByCondition('mediaFileFolder = {0}', [$this]),
            MediaFileFolder::getByCondition('parent = {0}', [$this])
        );
        $list = [];
        foreach ($childs as $child) {
            if ($child instanceof MediaFile) {
                $list[$child->id] = $child;
            } elseif ($child instanceof MediaFileFolder) {
                $list = ArrayUtils::merge($list, $child->getAllChildFiles());
            }
        }
        return $list;
    }

    /**
     * Delete
     * @param bool $force
     * @return void
     */
    public function delete(bool $force = false): void
    {
        $id = $this->id;
        parent::delete($force);
        self::deleteMultiple(MediaFile::getByCondition('mediaFileFolder = {0}', [$id]));
        self::deleteMultiple(MediaFileFolder::getByCondition('parent = {0}', [$id]));
    }
}