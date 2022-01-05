<?php

namespace Framelix\Myself\View\Backend;

use Framelix\Framelix\Lang;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Page;

/**
 * Index
 */
class Index extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

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
        // todo move to initialization not index page
        $pages = Page::getByCondition();
        if (!$pages) {
            // create default page
            $page = new Page();
            $page->title = "Homepage";
            $page->url = '';
            $page->flagDraft = false;
            $page->lang = Lang::$lang;
            $page->store();
        }
    }
}