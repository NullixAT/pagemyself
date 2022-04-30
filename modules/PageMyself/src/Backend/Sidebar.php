<?php

namespace Framelix\PageMyself\Backend;

use Framelix\PageMyself\View\Backend\PageEditor\Index;
use Framelix\PageMyself\View\Backend\WebsiteSettings;

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

        $this->addLink(\Framelix\PageMyself\View\Backend\Nav\Index::class);
        $this->showHtmlForLinkData();

        $this->addLink(WebsiteSettings::class);
        $this->showHtmlForLinkData();
    }
}