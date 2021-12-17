<?php

namespace Framelix\Myself\View\Backend\Nav;

use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Nav;

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
     * @var \Framelix\Myself\StorableMeta\Nav
     */
    private \Framelix\Myself\StorableMeta\Nav $meta;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->meta = new \Framelix\Myself\StorableMeta\Nav(new Nav());
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $this->meta->getTable(Nav::getByCondition())->show();
    }
}