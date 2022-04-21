<?php

namespace Framelix\PageMyself\View\Backend;

use Framelix\Framelix\View\Backend\View;

/**
 * Index
 */
class Index extends View
{

    /**
     * On request
     */
    public function onRequest(): void
    {
        \Framelix\Framelix\View::getUrl(PageEditor\Index::class)->redirect();
    }

    /**
     * Show the page content
     */
    public function showContent(): void
    {
    }
}