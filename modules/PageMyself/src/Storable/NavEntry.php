<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\PageMyself\View\Backend\Nav\Index;

/**
 * NavEntry
 * @property string $title
 * @property Page|null $page
 * @property MediaFile|null $image
 * @property string|null $url
 * @property int|null $sort
 * @property string|null $groupTitle
 * @property bool $flagShow
 */
class NavEntry extends StorableExtended
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
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return View::getUrl(Index::class)->setParameter('id', $this)->setHash('tabs:edit');
    }

    /**
     * Get public url
     * @return string
     */
    public function getPublicUrl(): string
    {
        if ($this->page) {
            return (string)$this->page->getPublicUrl()->setHash(
                str_starts_with($this->url ?? '', "#") ? substr($this->url, 1) : null
            );
        }
        return $this->url;
    }
}