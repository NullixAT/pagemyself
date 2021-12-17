<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ColorUtils;
use Framelix\Framelix\View;
use Framelix\Myself\View\Backend\Tag\Edit;

/**
 * Tag
 * @property int $category
 * @property string $name
 * @property string $color
 * @property int $sort
 */
class Tag extends StorableExtended
{
    public const CATEGORY_PAGE = 1;
    public const CATEGORY_NAV = 2;

    /**
     * All categories
     * @var array|int[]
     */
    public static array $categories = [
        self::CATEGORY_PAGE,
        self::CATEGORY_NAV
    ];

    /**
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return View::getUrl(Edit::class)->setParameter('id', $this)->setParameter('category', $this->category);
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
     * Get html string
     * @return string
     */
    public function getHtmlString(): string
    {
        return '<div class="myself-tag" style="background-color: ' . $this->color . '; color:' . ColorUtils::invertColor(
                $this->color,
                true
            ) . ';">' . $this->name . '</div>';
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this->name;
    }

}