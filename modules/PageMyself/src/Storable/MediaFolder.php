<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Utils\ArrayUtils;
use function array_reverse;
use function implode;

/**
 * MediaFolder
 * @property MediaFolder|null $parent
 * @property string $name
 */
class MediaFolder extends StorableExtended
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
            MediaFile::getByCondition('mediaFolder = {0}', [$this]),
            MediaFolder::getByCondition('parent = {0}', [$this])
        );
        $list = [];
        foreach ($childs as $child) {
            if ($child instanceof MediaFile) {
                $list[$child->id] = $child;
            } elseif ($child instanceof MediaFolder) {
                $list = ArrayUtils::merge($list, $child->getAllChildFiles());
            }
        }
        return $list;
    }

    /**
     * Get full name to this folder
     * @return string
     */
    public function getFullName(): string
    {
        $str = [$this->name];
        $parent = $this;
        while ($parent = $parent->parent) {
            $str[] = $parent->name;
        }
        return implode(" / ", array_reverse($str));
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
        self::deleteMultiple(MediaFile::getByCondition('mediaFolder = {0}', [$id]));
        self::deleteMultiple(MediaFolder::getByCondition('parent = {0}', [$id]));
    }
}