<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\PageMyself\Storable\Page;

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
    protected ?string $customUrl = "/sitemap.xml";

    /**
     * On request
     */
    public function onRequest(): void
    {
        header("content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $this->showSitemapUrl(View::getUrl(PageMyselfAbout::class));
        $pages = Page::getByCondition();
        foreach ($pages as $page) {
            if ($page->password) {
                continue;
            }
            $this->showSitemapUrl($page->url);
        }
        echo '</urlset>';
    }

    /**
     * Show additional sitemap url
     * @param string|Url $url Example: "/docs/hello"
     * @return void
     */
    public function showSitemapUrl(string|Url $url): void
    {
        echo '<url><loc>' . (is_string($url) ? View::getUrl(Index::class, ['url' => $url]) : $url) . '</loc></url>';
    }
}