<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\LayoutView;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\MediaFile;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\Storable\WebsiteSettings;
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
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'componentApiRequest') {
            $componentBlock = ComponentBlock::getById(
                Request::getGet('data[componentBlockId]') ?? $jsCall->parameters['componentBlockId'] ?? null
            );
            if (!$componentBlock) {
                return;
            }
            $component = $componentBlock->getComponentInstance();
            $component->onApiRequest(
                Request::getGet('data[action]') ?? $jsCall->parameters['action'],
                Request::getGet('data[params]') ?? $jsCall->parameters['params'] ?? null
            );
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        $applicationUrl = Url::getApplicationUrl();
        $url = Url::create();
        $relativeUrl = trim($url->getRelativePath($applicationUrl), "/");
        $this->page = $this->page ?? Page::getByConditionOne(
                'url = {0} && category = {1}',
                [$relativeUrl, Page::CATEGORY_PAGE]
            );
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

        $themeFolder = __DIR__ . "/../../public/themes/" . $this->theme->themeId;

        $this->includeCompiledFilesForModule("Framelix");
        $this->includeCompiledFilesForModule(FRAMELIX_MODULE);
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "pagemyself");
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "components");
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "components");

        $themeCssFiles = FileUtils::getFiles($themeFolder . "/stylesheets", "~\.css$~i", true);
        foreach ($themeCssFiles as $themeCssFile) {
            $this->addHeadHtml(HtmlUtils::getIncludeTagForUrl(Url::getUrlToFile($themeCssFile)));
        }
        $themeJsFiles = FileUtils::getFiles($themeFolder . "/scripts", "~\.js$~i", true);
        foreach ($themeJsFiles as $themeJsFile) {
            $this->addHeadHtml(HtmlUtils::getIncludeTagForUrl(Url::getUrlToFile($themeJsFile)));
        }

        $favicon = MediaFile::getById(WebsiteSettings::get('websitesetting_favicon'));
        if ($favicon) {
            $this->addHeadHtmlAfterInit('<link rel="icon" href="' . $favicon->getUrl() . '">');
        } else {
            $this->addHeadHtmlAfterInit(
                '<link rel="icon" href="' . Url::getUrlToFile(__DIR__ . "/../../public/img/logo-squared.svg") . '">'
            );
        }
        if ($settingValue = WebsiteSettings::get('websitesetting_og_site_name')) {
            $this->addHeadHtmlAfterInit(
                '<meta property="og:site_name" content="' . HtmlUtils::escape($settingValue) . '"/>'
            );
        }
        if ($settingValue = WebsiteSettings::get('websitesetting_og_image')) {
            $image = MediaFile::getById($settingValue);
            if ($image) {
                $this->addHeadHtmlAfterInit(
                    '<meta property="og:image" content="' . $image->getUrl() . '"/>'
                );
            }
        }
        if ($settingValue = WebsiteSettings::get('websitesetting_og_title')) {
            $this->addHeadHtmlAfterInit(
                '<meta property="og:title" content="' . HtmlUtils::escape($settingValue) . '"/>'
            );
        }
        if ($settingValue = WebsiteSettings::get('websitesetting_og_description')) {
            $this->addHeadHtmlAfterInit(
                '<meta property="og:description" content="' . HtmlUtils::escape($settingValue) . '"/>'
            );
        }
        if ($settingValue = WebsiteSettings::get('websitesetting_author')) {
            $this->addHeadHtmlAfterInit('<meta property="author" content="' . HtmlUtils::escape($settingValue) . '"/>');
        }
        if ($settingValue = WebsiteSettings::get('websitesetting_keywords')) {
            $this->addHeadHtmlAfterInit(
                '<meta property="keywords" content="' . HtmlUtils::escape($settingValue) . '"/>'
            );
        }
        if ($settingValue = WebsiteSettings::get('websitesetting_headhtml')) {
            $this->addHeadHtmlAfterInit($settingValue);
        }
        $this->addHeadHtmlAfterInit(
            '
            <meta property="og:type" content="website" />
            <meta property="og:url" content="' . Url::create() . '" />
            <meta name="generator" content="PageMyself Website Builder" />
            <script>            
                PageMyself.config = ' . JsonUtils::encode([
                'componentApiRequestUrl' => JsCall::getCallUrl(__CLASS__, 'componentApiRequest')
            ]) . ';
            </script>
        '
        );

        $this->theme->onViewSetup($this);

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
        if ($settingValue = WebsiteSettings::get('websitesetting_bodyhtml')) {
            echo $settingValue;
        }
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