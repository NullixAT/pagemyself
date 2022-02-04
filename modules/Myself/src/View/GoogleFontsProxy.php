<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\StringUtils;
use Framelix\Framelix\View;

use function clearstatcache;
use function file_exists;
use function file_put_contents;
use function header;
use function is_string;
use function preg_match_all;
use function str_replace;

/**
 * GoogleFontsProxy
 * Proxy fonts.google.com include requests and store files locally
 * Integrated because in europe there has been some law cases where this behaviour is problemeatic (data protection)
 */
class GoogleFontsProxy extends View
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
    protected ?string $customUrl = "~/googlefontsproxy/(?<font>[a-z0-9-]+)~";

    /**
     * On request
     */
    public function onRequest(): void
    {
        $font = Request::getGet('font');
        if (!is_string($font)) {
            return;
        }
        header("content-type: text/css");
        $filename = $this->customUrlParameters['font'];
        $proxyFolder = __DIR__ . "/../../public/googlefontsproxy";
        $localCssFile = "$proxyFolder/" . $filename . ".css";
        $url = 'https://fonts.googleapis.com/css2?family=' . $font . "&display=swap";
        $browser = Browser::create();
        $browser->url = $url;
        $browser->sendRequest();
        if (!$browser->responseBody) {
            return;
        }
        $css = $browser->responseBody;
        preg_match_all("~url\s*\((.*?)\)~i", $css, $matches);
        if ($matches[0] ?? null) {
            foreach ($matches[1] as $key => $url) {
                $browser->url = $url;
                $browser->sendRequest();
                $woffFilename = StringUtils::slugify($url, true, false);
                $woffPath = $proxyFolder . "/" . StringUtils::slugify($url, true, false);
                if (!file_exists($woffPath)) {
                    file_put_contents($woffPath, $browser->responseBody);
                    clearstatcache();
                }
                $css = str_replace($url, $woffFilename, $css);
            }
        }
        file_put_contents(
            $localCssFile,
            $css
        );
        echo $css;
    }
}