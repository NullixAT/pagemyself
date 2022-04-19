<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\PageMyself\View\Backend\PageLayout\Index;

/**
 * Page Layout
 * @property string $title
 * @property bool $flagDefault
 * @property mixed $layoutSettings
 */
class PageLayout extends StorableExtended
{
    /**
     * Get the default layout
     * @return PageLayout
     */
    public static function getDefault(): PageLayout
    {
        $layout = self::getByConditionOne('flagDefault = 1');
        if (!$layout) {
            $layout = new self();
            $layout->title = 'Default';
            $layout->flagDefault = true;
            $layout->layoutSettings = [
                'design' => 'default',
                'align' => 'center',
                'maxWidth' => '900',
                'nav' => 'top'
            ];
            $layout->store();
        }
        return $layout;
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
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return View::getUrl(Index::class)->setParameter('id', $this)->setHash('tabs:edit');
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