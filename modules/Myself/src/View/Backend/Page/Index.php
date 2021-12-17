<?php

namespace Framelix\Myself\View\Backend\Page;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Page;

/**
 * Tab view
 */
class Index extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,nav";

    /**
     * The storable
     * @var Page
     */
    private Page $storable;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = Page::getByIdOrNew(Request::getGet('id'));
        if ($this->storable->id) {
            $this->pageTitle = $this->storable->getHtmlString();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $homepage = Page::getByConditionOne('url = {0}', ['']);
        if (!$homepage) {
            echo '<div class="framelix-alert framelix-alert-warning">' . Lang::get(
                    '__myself_view_backend_page_index_missing_homepage__'
                ) . '</div>';
        }
        $tabs = new Tabs();
        $tabs->addTab('edit', null, new Edit());
        $tabs->addTab('entries', null, new Entries());
        $tabs->show();
    }
}