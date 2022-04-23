<?php

namespace Framelix\PageMyself;

use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\Page;

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
     * Show the page content
     */
    abstract public function showContent(): void;

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
     * Show <nav> block based on current page
     * @return void
     */
    public function showNavigation(): void
    {
        $condition = 'flagNav = 1';
        if (!User::get()) {
            $condition = 'flagDraft = 0';
        }
        $pages = Page::getByCondition($condition, sort: "+sort");
        ?>
        <nav class="page-nav">
            <ul>
                <?php
                $pagesCollected = [];
                foreach ($pages as $page) {
                    if (isset($pagesCollected[$page->id])) {
                        continue;
                    }
                    $group = [];
                    if ($page->navGroup) {
                        foreach ($pages as $subPage) {
                            if (isset($pagesCollected[$subPage->id])) {
                                continue;
                            }
                            if ($subPage->navGroup === $page->navGroup) {
                                $group[$subPage->id] = $subPage;
                                $pagesCollected[$subPage->id] = true;
                            }
                        }
                    }
                    if ($group) {
                        ?>
                        <li>
                            <button class="nav-entry"><?= HtmlUtils::escape($page->navGroup) ?></button>
                            <ul class="hidden">
                                <?php
                                foreach ($group as $subPage) {
                                    $this->showNavigationEntry($subPage);
                                }
                                ?>
                            </ul>
                        </li>
                        <?php
                    } else {
                        $this->showNavigationEntry($page);
                    }
                }
                ?>
            </ul>
        </nav>
        <?php
    }

    /**
     * Show navigation entry for given page
     * @param Page $page
     */
    public function showNavigationEntry(Page $page): void
    {
        $url = $page->category === Page::CATEGORY_PAGE ? $page->getPublicUrl() : $page->link;
        $target = $page->category === Page::CATEGORY_PAGE ? '' : 'target="_blank"';
        ?>
        <li>
            <span></span>
            <a class="nav-entry <?= $page === $this->page ? 'nav-entry-active' : '' ?>"
               href="<?= $url ?>" <?= $target ?>><?= HtmlUtils::escape($page->titleNav ?: $page->title) ?></a>
            <span></span>
        </li>
        <?php
    }

    /**
     * Show components for given placement
     * @param string $placement
     * @param bool $mainContent Set this true, for the one container that represent the main page content
     * @return void
     */
    final public function showComponentBlocks(string $placement, bool $mainContent = false): void
    {
        if (
            ($this->page->password ?? null)
            && !Session::get('pagemyself-page-password-' . md5($this->page->password))
        ) {
            if (!$mainContent) {
                return;
            }
            $form = new Form();
            $form->id = "pagepassword";
            $form->submitAsync = false;
            $form->submitWithEnter = true;

            $field = new Password();
            $field->name = "password";
            $field->label = "__pagemyself_page_password__";
            $form->addField($field);

            $form->addSubmitButton('login', '__pagemyself_page_login__');
            $form->show();
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
    public function showComponentBlock(ComponentBlock $componentBlock): void
    {
        $instance = $componentBlock->getComponentInstance();
        $jsClassName = "PageMyselfComponent" . ClassUtils::getClassBaseName($componentBlock->blockClass);
        ?>
        <div class="component-block <?= ClassUtils::getHtmlClass($componentBlock->blockClass) ?>"
             id="block-<?= $componentBlock ?>" data-id="<?= $componentBlock ?>">
            <?php
            $instance->show();
            ?>
        </div>
        <script>
          (function () {
            const block = new <?=$jsClassName?>(<?=$componentBlock?>)
            block.init()
          })()
        </script>
        <?php
    }

    /**
     * Add setting fields to the settings form that is displayed when the user click the settings icon
     */
    public function addSettingFields(Form $form): void
    {
    }
}