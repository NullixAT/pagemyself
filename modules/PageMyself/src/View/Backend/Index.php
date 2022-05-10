<?php

namespace Framelix\PageMyself\View\Backend;

use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\Utils\PageExportImport;

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
        // create initial content and pages on first visit
        if (!Page::getByConditionOne()) {
            $page = Page::getDefault();
            PageExportImport::importFromJson(
                $page,
                file_get_contents(__DIR__ . "/../../../page-templates/default.json")
            );
        }
        \Framelix\Framelix\View::getUrl(PageEditor\Index::class)->redirect();
    }

    /**
     * Show the page content
     */
    public function showContent(): void
    {
    }
}