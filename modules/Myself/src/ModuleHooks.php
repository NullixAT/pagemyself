<?php

namespace Framelix\Myself;

use Framelix\Framelix\Config;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\View\Index;

use function call_user_func_array;
use function class_exists;
use function is_string;
use function method_exists;

/**
 * ModuleHooks to run when something is happening
 */
class ModuleHooks
{
    /**
     * Call a hook for all installed modules, if hook exist
     * @param string $hookMethod
     * @param array $parameters
     * @param string|null $limitModule If set, only run for this module
     * @return void
     */
    final public static function callHook(string $hookMethod, array $parameters = [], ?string $limitModule = null): void
    {
        foreach (Config::$loadedModules as $module) {
            $hookClass = "\\Framelix\\$module\\ModuleHooks";
            if (!class_exists($hookClass) || !method_exists($hookClass, $hookMethod)) {
                continue;
            }
            if ($limitModule && $limitModule !== $module) {
                continue;
            }
            call_user_func_array([$hookClass, $hookMethod], $parameters);
        }
    }

    /**
     * Executed after the module has been installed via backend
     * @return void
     */
    public static function afterInstall(): void
    {
    }

    /**
     * Executed before the pages content is shown
     * Use this to add metadata for example
     * Do not do jobs here that can impact performance as it is done on every page request
     * @param Index $view
     * @return void
     */
    public static function beforeViewShowContent(Index $view): void
    {
    }

    /**
     * Executed right after <body> tag has been opened
     * Use this to add html into that position
     * Do not do jobs here that can impact performance as it is done on every page request
     * @return void
     */
    public static function afterBodyTagOpened(): void
    {
    }

    /**
     * Executed right before </body> tag is closed
     * Use this to add html into that position
     * Do not do jobs here that can impact performance as it is done on every page request
     * @return void
     */
    public static function beforeBodyTagClosed(): void
    {
    }

    /**
     * Executed right before the <div> tag of the given pageblock is opened
     * Use this to add html into that position
     * Do not do jobs here that can impact performance as it is done on every page request
     * @param BlockBase $layoutBlock
     * @return void
     */
    public static function beforePageBlockTagOpened(BlockBase $layoutBlock): void
    {
    }

    /**
     * Executed right after the <div> tag of the given pageblock is closed
     * Use this to add html into that position
     * Do not do jobs here that can impact performance as it is done on every page request
     * @param BlockBase $layoutBlock
     * @return void
     */
    public static function afterPageBlockTagClosed(BlockBase $layoutBlock): void
    {
    }

    /**
     * Show sitemap urls
     * @return void
     */
    public static function showSitemapUrls(): void
    {
    }

    /**
     * Show additional sitemap url
     * @param string|Url $url Example: "/docs/hello"
     * @return void
     */
    public static function showSitemapUrl(string|Url $url): void
    {
        echo '<url><loc>' . (is_string($url) ? View::getUrl(Index::class, ['url' => $url]) : $url) . '</loc></url>';
    }
}