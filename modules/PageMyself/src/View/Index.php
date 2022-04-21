<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\View;
use Framelix\Framelix\View\LayoutView;
use Framelix\PageMyself\ModuleHooks;
use Framelix\PageMyself\PageBlock\Base;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\Storable\PageBlock;
use Framelix\PageMyself\Storable\PageLayout;
use function trim;

/**
 * Index
 */
class Index extends LayoutView
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Custom url
     * @var string|null
     */
    protected ?string $customUrl = "~(?<url>.*)~";

    /**
     * Reduce priority to act as a "catch all" fallback when no other url matches
     * @var int
     */
    protected int $urlPriority = -1;

    /**
     * Multilanguage disable
     * @var bool
     */
    protected bool $multilanguage = false;

    /**
     * Current page
     * @var Page|null
     */
    private ?Page $page;

    /**
     * Current page blocks
     * @var PageBlock[]
     */
    private array $pageBlocks = [];

    /**
     * On request
     */
    public function onRequest(): void
    {
        $applicationUrl = Url::getApplicationUrl();
        $url = Url::create();
        $relativeUrl = trim($url->getRelativePath($applicationUrl), "/");
        $this->page = $this->page ?? Page::getByConditionOne('url = {0}', [$relativeUrl]);
        if (!$this->page && $relativeUrl) {
            Url::getApplicationUrl()->redirect();
        }
        if (!$this->page) {
            $this->page = Page::getDefault();
        }
        if (($this->page->flagDraft ?? null) && !User::get()) {
            $this->page = null;
        }
        if ($this->page) {
            $this->pageTitle = $this->page->title;
            if (Request::getPost('framelix-form-pagepassword')) {
                if (Request::getPost('password') === $this->page->password) {
                    Session::set('pagemyself-page-password-' . md5($this->page->password), true);
                } else {
                    Toast::error('__pagemyself_password_incorrect__');
                }
                Url::getBrowserUrl()->redirect();
            }
            $this->pageBlocks = $this->page->getPageBlocks();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content with page layout
     * @return void
     */
    public function showContentWithLayout(): void
    {
        Buffer::start();
        if ($this->contentCallable) {
            call_user_func_array($this->contentCallable, []);
        } else {
            $this->showContent();
        }

        $this->includeCompiledFilesForModule("Framelix");
        $this->includeCompiledFilesForModule(FRAMELIX_MODULE);
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "pagemyself");
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "pageblocks");
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "pagemyself");
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "pageblocks");

        $pageContent = Buffer::getAll();

        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->set('data-view', get_class(self::$activeView));
        $htmlAttributes->set('data-page', $this->page);
        $htmlAttributes->set('data-color-scheme-force', 'light');
        $htmlAttributes->set('lang', Lang::$lang);

        Buffer::start();
        echo '<!DOCTYPE html>';
        echo '<html ' . $htmlAttributes . '>';
        $this->showDefaultPageStartHtml();
        echo '<body>';
        ModuleHooks::callHook('afterBodyTagOpened', [$this]);
        echo '<div class="framelix-page">';
        echo $pageContent;
        echo '</div>';
        ?>
        <script>
          Framelix.initLate()
        </script>
        <?php
        ModuleHooks::callHook('beforeBodyTagClosed', [$this]);
        echo '</body></html>';
        Buffer::flush();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if (!$this->page) {
            http_response_code(404);
            echo '<div style="text-align: center"><div style="padding:30px;
background: white; color:#222; font-weight: bold">' . Lang::get('__pagemyself_page_not_exist__');
            echo '</div></div>';
            return;
        }
        $layout = $this->page->layout ?? PageLayout::getDefault();
        $nav = $layout->layoutSettings['nav'] ?? 'top';
        $align = $layout->layoutSettings['align'] ?? 'center';
        $maxWidth = $layout->layoutSettings['maxwidth'] ?? '900';
        ?>
        <div class="page page-align-<?= $align ?> page-nav-<?= $nav ?>">
            <div class="page-inner" style="max-width:<?= $maxWidth ?>px;">
                <?php
                if ($nav !== 'none') {
                    $condition = 'flagNav = 1';
                    if (!User::get()) {
                        $condition = 'flagDraft = 0';
                    }
                    $pages = Page::getByCondition($condition, sort: "+sort");
                    ?>
                    <nav class="page-nav">
                        <?php
                        if ($nav !== 'top') {
                            $this->showPageBlocks('nav-before');
                        }
                        ?>
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
                                        <span></span>
                                        <button class="nav-entry"><?= HtmlUtils::escape($page->navGroup) ?></button>
                                        <span></span>
                                        <ul class="hidden">
                                            <?php
                                            foreach ($group as $subPage) {
                                                $this->showNavEntry($subPage);
                                            }
                                            ?>
                                        </ul>
                                    </li>
                                    <?php
                                } else {
                                    $this->showNavEntry($page);
                                }
                            }
                            if ($nav === 'top') {
                                ?>
                                <li class="nav-entry-hidden show-more">
                                    <span></span>
                                    <button class="nav-entry"><?= Lang::get('__pagemyself_more_nav__') ?></button>
                                    <span></span>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                        if ($nav !== 'top') {
                            $this->showPageBlocks('nav-after');
                        }
                        ?>
                    </nav>
                    <?php
                }
                ?>
                <div class="page-content">
                    <?php
                    if (
                        ($this->page->password ?? null)
                        && !Session::get('pagemyself-page-password-' . md5($this->page->password))
                    ) {
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
                    } else {
                        $this->showPageBlocks('content');
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Show nav entry
     * @param Page $page
     */
    private function showNavEntry(Page $page): void
    {
        $url = $page->category === Page::CATEGORY_PAGE ? View::getUrl(
            __CLASS__,
            ['url' => $page->url]
        ) : $page->link;
        $target = $page->category === Page::CATEGORY_PAGE ? '' : 'target="_blank"';
        ?>
        <li>
            <span></span>
            <a class="nav-entry <?= $page === $this->page ? 'nav-entry-active' : '' ?>"
               href="<?= $url ?>" <?= $target ?>><?= HtmlUtils::escape($page->title) ?></a>
            <span></span>
        </li>
        <?php
    }

    /**
     * Show page blocks
     * @param string $placement
     * @return void
     */
    private function showPageBlocks(string $placement): void
    {
        echo '<div class="page-blocks" data-placement="' . $placement . '">';
        foreach ($this->pageBlocks as $pageBlock) {
            $instance = Base::createInstance($pageBlock);
            $jsClassName = "PageBlock" . ClassUtils::getClassBaseName($pageBlock->blockClass);
            ?>
            <div class="page-block <?= ClassUtils::getHtmlClass($pageBlock->blockClass) ?>"
                 id="block-<?= $pageBlock ?>" data-id="<?= $pageBlock ?>">
                <?php
                $instance->show();
                ?>
            </div>
            <script>
              (function () {
                const block = new <?=$jsClassName?>(<?=$pageBlock?>)
                block.init()
              })()
            </script>
            <?php
        }
        echo '</div>';
    }
}