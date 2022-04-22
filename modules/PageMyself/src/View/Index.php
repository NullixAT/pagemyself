<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\View\LayoutView;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\ThemeBase;
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
     * The theme
     * @var ThemeBase
     */
    private ThemeBase $theme;

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
        if (($this->page->flagDraft ?? null) && !User::get()) {
            $this->page = null;
        }
        if (!$this->page) {
            $this->page = Page::getDefault();
        }

        if (Request::getPost('framelix-form-pagepassword')) {
            if (Request::getPost('password') === $this->page->password) {
                Session::set('pagemyself-page-password-' . md5($this->page->password), true);
            } else {
                Toast::error('__pagemyself_password_incorrect__');
            }
            Url::getBrowserUrl()->redirect();
        }
        $this->theme = $this->page->getThemeInstance();
        $this->pageTitle = $this->page->title;
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

        $themeFolder = __DIR__ . "/../../public/themes/" . $this->theme->getThemeId();

        $this->includeCompiledFilesForModule("Framelix");
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "pagemyself");
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "components");
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "pagemyself");
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "components");

        $themeCssFiles = FileUtils::getFiles($themeFolder . "/stylesheets", "~\.css$~i", true);
        foreach ($themeCssFiles as $themeCssFile) {
            $this->addHeadHtml(HtmlUtils::getIncludeTagForUrl(Url::getUrlToFile($themeCssFile)));
        }
        $themeJsFiles = FileUtils::getFiles($themeFolder . "/scripts", "~\.js$~i", true);
        foreach ($themeJsFiles as $themeJsFile) {
            $this->addHeadHtml(HtmlUtils::getIncludeTagForUrl(Url::getUrlToFile($themeJsFile)));
        }

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
        echo '<div class="framelix-page">';
        echo $pageContent;
        echo '</div>';
        ?>
        <script>
          Framelix.initLate()
        </script>
        <?php
        echo '</body></html>';
        Buffer::flush();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $this->theme->showContent();
    }
}