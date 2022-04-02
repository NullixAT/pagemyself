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
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show the page content
     */
    public function showContent(): void
    {
    }
}