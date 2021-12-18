<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Lang;
use Framelix\Framelix\View;
use Framelix\Myself\Storable\Nav;
use Framelix\Myself\Storable\Page;

use function header;

/**
 * XML Sitemap
 */
class Sitemap extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Multilanguage disable
     * @var bool
     */
    protected bool $multilanguage = false;

    /**
     * Custom url
     * @var string|null
     */
    protected ?string $customUrl = "~sitemap.xml~";

    /**
     * On request
     */
    public function onRequest(): void
    {
        $pages = Page::getByCondition('flagDraft = false');
        header("content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        echo '<url>
                <loc>' . View::getUrl(PageMyselfAbout::class) . '</loc>
            </url>';
        foreach ($pages as $page) {
            echo '<url>
                <loc>' . View::getUrl(Index::class, ['url' => $page->url]) . '</loc>
            </url>';
        }
        echo '</urlset>';
    }

    /**
     * Show xml recursive for all childs of given parent
     * @param Nav|null $parent
     * @return Nav[]
     */
    private function showUrlsRecursive(?Page $parent): array
    {
        $condition = 'parent IS NULL';
        if ($parent) {
            $condition = "parent = " . $parent;
        }
        $condition .= " && flagDraft = false";
        $entries = Nav::getByCondition(
            $condition,
            [$this->pageBlock->page->lang ?? Lang::$lang],
            sort: ['+sort', '+title']
        );
        foreach ($entries as $entry) {
            echo '<url>
    <loc>' . View::getUrl(Index::class, ['url' => $entry->link]) . '</loc>
    <lastmod>2018-06-04</lastmod>
  </url>';
        }
        return Nav::getByCondition(
            $condition,
            [$this->pageBlock->page->lang ?? Lang::$lang],
            sort: ['+sort', '+title']
        );
    }
}