<?php

namespace Framelix\PageMyself;

use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\PageMyself\Component\ComponentBase;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\NavEntry;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\Storable\WebsiteSettings;
use Framelix\PageMyself\View\Index;

use function array_pop;
use function explode;
use function file_exists;
use function get_class;

/**
 * Layout base class
 */
abstract class ThemeBase
{
    /**
     * Cache
     * @var array
     */
    private static array $cache = [];

    /**
     * The internal theme id (folder name
     * @var string
     */
    public string $themeId;

    /**
     * The current page
     * @var Page
     */
    public Page $page;

    /**
     * Get list of available themes
     * @return array
     */
    public static function getAvailableList(): array
    {
        $cacheKey = __METHOD__;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        $folder = __DIR__ . "/../public/themes";
        $files = scandir($folder);
        $arr = [];
        foreach ($files as $file) {
            $themeFile = $folder . "/$file/Theme.php";
            if ($file[0] === "." || !file_exists($themeFile)) {
                continue;
            }
            $arr[$file] = [
                'theme' => $file
            ];
        }
        self::$cache[$cacheKey] = $arr;
        return $arr;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $exp = explode("\\", get_class($this));
        array_pop($exp);
        $this->themeId = array_pop($exp);
        $cacheKey = $this->themeId . "_initialized";
        if (!isset(self::$cache[$cacheKey])) {
            // include lang
            $folder = __DIR__ . "/../public/themes/$this->themeId";
            foreach (Lang::getEnabledLanguages() as $language) {
                $filePath = $folder . "/lang/$language.json";
                Lang::addValuesForFile($language, $filePath);
            }
        }
    }

    /**
     * Show the page content
     */
    abstract public function showContent(): void;

    /**
     * Show navigation entries
     * @return void
     */
    public function showNavigation(): void
    {
        $navEntries = NavEntry::getByCondition("flagShow = 1", sort: "+sort");
        $stickyNav = $this->getSettingValue('stickyNav') !== false;
        ?>
        <nav class="page-nav <?= $stickyNav ? 'page-nav-sticky pagemyself-jumpmark-offset' : '' ?>">
            <div class="page-nav-inner">
                <ul>
                    <?php
                    $pagesCollected = [];
                    foreach ($navEntries as $navEntry) {
                        if (isset($pagesCollected[$navEntry->id])) {
                            continue;
                        }
                        $group = [];
                        if ($navEntry->groupTitle) {
                            foreach ($navEntries as $subNavEntry) {
                                if (isset($pagesCollected[$subNavEntry->id])) {
                                    continue;
                                }
                                if ($subNavEntry->groupTitle === $navEntry->groupTitle) {
                                    $group[$subNavEntry->id] = $subNavEntry;
                                    $pagesCollected[$subNavEntry->id] = true;
                                }
                            }
                        }
                        if ($group) {
                            $isActive = false;
                            foreach ($group as $subNavEntry) {
                                $isActive = $this->page === $subNavEntry->page;
                                if ($isActive) {
                                    break;
                                }
                            }
                            ?>
                            <li>
                                <span></span>
                                <button class="nav-entry nav-entry-group <?= $isActive ? 'nav-entry-active' : '' ?>"><?= HtmlUtils::escape(
                                        $navEntry->groupTitle
                                    ) ?></button>
                                <span></span>
                                <ul class="hidden">
                                    <?php
                                    foreach ($group as $subNavEntry) {
                                        $this->showNavigationEntry($subNavEntry);
                                    }
                                    ?>
                                </ul>
                            </li>
                            <?php
                        } else {
                            $this->showNavigationEntry($navEntry);
                        }
                    }
                    ?>
                </ul>
            </div>
        </nav>
        <?php
    }

    /**
     * Show navigation entry for given entry
     * @param NavEntry $navEntry
     */
    public function showNavigationEntry(NavEntry $navEntry): void
    {
        $url = $navEntry->getPublicUrl();
        $target = $navEntry->page ? '' : 'target="_blank"';
        $title = HtmlUtils::escape($navEntry->title);
        $isImage = $navEntry->image?->isImage();
        if ($isImage) {
            $title = '<img src="' . $navEntry->image->getUrl(
                    500
                ) . '" alt="' . $title . '" data-image-type="' . $navEntry->image->extension . '">';
        }
        $isActive = $navEntry->page === $this->page && !str_contains($url, "#") && !$isImage;
        ?>
        <li>
            <span></span>
            <a class="nav-entry <?= $isActive ? 'nav-entry-active' : '' ?> <?= $isImage ? 'nav-entry-image' : '' ?>"
               href="<?= $url ?>" <?= $target ?>><?= $title ?></a>
            <span></span>
        </li>
        <?php
    }

    /**
     * Show components for given placement
     * @param string $placement
     * @param bool $mainContent Set this false, for the contains that no represents main content (sidebar, etc..)
     * @return void
     */
    final public function showComponentBlocks(string $placement, bool $mainContent = true): void
    {
        if (
            ($this->page->password ?? null)
            && !Session::get('pagemyself-page-password-' . md5($this->page->password))
        ) {
            if (!$mainContent) {
                return;
            }
            echo '<div class="pagemyself-password-form">';
            $form = new Form();
            $form->id = "pagepassword";
            $form->submitAsync = false;
            $form->submitWithEnter = true;

            $field = new Password();
            $field->name = "password";
            $field->label = "__pagemyself_page_password__";
            $field->maxWidth = null;
            $form->addField($field);

            $form->addSubmitButton('login', '__pagemyself_page_login__');
            $form->show();
            echo '</div>';
            return;
        }
        echo '<div class="component-blocks" data-placement="' . $placement . '">';
        foreach ($this->page->getComponentBlocks() as $componentBlock) {
            if ($componentBlock->placement !== $placement) {
                continue;
            }
            $this->showComponentBlock($componentBlock);
        }
        echo '</div>';
    }

    /**
     * Show component block
     * @param ComponentBlock $componentBlock
     * @return void
     */
    final public function showComponentBlock(ComponentBlock $componentBlock): void
    {
        $instance = $componentBlock->getComponentInstance();
        $jsClassName = "PageMyselfComponent" . ClassUtils::getClassBaseName($componentBlock->blockClass);
        ?>
        <div class="component-block <?= ClassUtils::getHtmlClass($componentBlock->blockClass) ?>"
             id="block-<?= $componentBlock ?>" data-id="<?= $componentBlock ?>">
            <?php
            $this->showComponentContent($instance);
            ?>
        </div>
        <script>
          (function () {
            const block = new <?=$jsClassName?>(<?=$componentBlock?>)
            block.init(<?=JsonUtils::encode($instance->getJavascriptInitParameters())?>)
          })()
        </script>
        <?php
    }

    /**
     * Get setting value
     * @param string $key
     * @return mixed
     */
    final public function getSettingValue(string $key): mixed
    {
        return WebsiteSettings::get('theme_' . $this->themeId . "_" . $key);
    }

    /**
     * Show component content
     * @param ComponentBase $componentInstance
     * @return void
     */
    public function showComponentContent(ComponentBase $componentInstance): void
    {
        $componentInstance->show();
    }

    /**
     * Add fields to the theme settings form that is displayed when the user click the theme settings icon
     * @param Form $form
     * @return void
     */
    public function addThemeSettingFields(Form $form): void
    {
    }

    /**
     * Add fields to the component block settings form that is displayed when the user click the component settings icon
     * @param Form $form
     * @param ComponentBase $component
     * @return void
     */
    public function addComponentSettingFields(Form $form, ComponentBase $component): void
    {
    }

    /**
     * On view setup
     * Use this to insert <head> data with $view->addHeadHtmlAfterInit() if you need some additional initializing stuff
     * @param Index $view
     * @return void
     */
    public function onViewSetup(Index $view): void
    {
    }
}