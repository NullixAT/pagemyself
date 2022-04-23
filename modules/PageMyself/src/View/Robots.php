<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\View;

use function header;

/**
 * Robots.txt
 */
class Robots extends View
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
    protected ?string $customUrl = "/robots.txt";

    /**
     * On request
     */
    public function onRequest(): void
    {
        header("content-type: text/plain");
        echo 'allow: /pagemyselfabout' . "\n";
        echo 'allow: *' . "\n";
        echo 'sitemap: ' . View::getUrl(Sitemap::class) . "\n";
    }
}