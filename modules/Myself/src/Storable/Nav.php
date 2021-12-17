<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\Myself\View\Backend\Nav\Index;

use function basename;

/**
 * Nav
 * @property Tag[]|null $pageTagsVisible
 * @property Tag[]|null $tags
 * @property Nav|null $parent
 * @property Page|null $page
 * @property string|null $title
 * @property string|null $link
 * @property string|null $target
 * @property bool $flagDraft
 * @property int|null $sort
 */
class Nav extends StorableExtended
{
    /**
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return View::getUrl(Index::class)->setParameter('id', $this)->setHash('tabs:edit');
    }

    /**
     * Get label
     * @return string
     */
    public function getLabel(): string
    {
        return $this->title ?: $this->page->title ?? basename($this->link ?: "NotSet");
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
        return $this->title;
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this->title;
    }

}