<?php

namespace Framelix\Myself\Backend;

use Framelix\Myself\Storable\Tag;
use Framelix\Myself\View\Backend\Page\Index;
use Framelix\Myself\View\Backend\Tag\Edit;

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
        $this->addLink(
            \Framelix\Myself\View\Index::class,
            "__myself_open_website_editor__",
            "home",
            "_blank",
            ['editMode' => 1],
            ['url' => '']
        );
        $this->showHtmlForLinkData();

        $this->addLink(Index::class, icon: "article");
        $this->addLink(\Framelix\Myself\View\Backend\Nav\Index::class, icon: "menu");
        $this->showHtmlForLinkData();

        $this->startGroup('__myself_tags__', 'tag');
        foreach (Tag::$categories as $category) {
            $this->addLink(
                Edit::class,
                "__myself_tag_category_{$category}__",
                urlParameters: ["category" => $category]
            );
        }
        $this->showHtmlForLinkData();
    }
}