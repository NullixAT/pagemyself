<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\View;
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
    protected ?string $customUrl = "/sitemap.xml";

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
}