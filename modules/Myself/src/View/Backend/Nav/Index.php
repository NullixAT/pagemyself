<?php

namespace Framelix\Myself\View\Backend\Nav;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Nav;

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
     * @var Nav
     */
    private Nav $storable;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = Nav::getByIdOrNew(Request::getGet('id'));
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
        $tabs = new Tabs();
        $tabs->addTab('edit', null, new Edit());
        $tabs->addTab('entries', null, new Entries());
        $tabs->addTab('arrange', null, new Arrange());
        $tabs->show();
    }
}