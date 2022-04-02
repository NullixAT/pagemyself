<?php

namespace Framelix\PageMyself\Backend;


use Framelix\PageMyself\View\Backend\PageEditor\Index;

/**
 * Backend sidebar
 */
class Sidebar extends \Framelix\Framelix\Backend\Sidebar
{
    /**
     * Show the navigation content
     */
    public function showContent(): void
    {
        $this->addLink(Index::class);
        $this->showHtmlForLinkData();

        $this->addLink(\Framelix\PageMyself\View\Backend\Page\Index::class);
        $this->showHtmlForLinkData();
    }
}