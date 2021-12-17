<?php

namespace Framelix\Myself\View\Backend\Page;

use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Page;

/**
 * Entries
 */
class Entries extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,nav";

    /**
     * The storable meta
     * @var \Framelix\Myself\StorableMeta\Page
     */
    private \Framelix\Myself\StorableMeta\Page $meta;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->meta = new \Framelix\Myself\StorableMeta\Page(new Page());
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $pages = Page::getByCondition();
        $this->meta->getTable($pages)->show();
    }
}