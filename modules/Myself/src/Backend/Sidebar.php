<?php

namespace Framelix\Myself\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Myself\View\Backend\Page\Index;

use function file_exists;

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
        // add custom navigation entries for extra modules
        foreach (Config::$loadedModules as $module) {
            if ($module === "Framelix" || $module === "Myself") {
                continue;
            }
            $file = FileUtils::getModuleRootPath($module) . "/src/View/Backend/$module/Index.php";
            if (file_exists($file)) {
                $viewClassIndex = "Framelix\\$module\\View\\Backend\\$module\\Index";
                $this->addLink($viewClassIndex);
            }
        }
        $this->showHtmlForLinkData();
    }
}