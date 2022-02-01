<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\AppUpdate;
use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;
use Framelix\Framelix\View\LayoutView;
use Framelix\Myself\BlockLayout\BlockLayoutEditor;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\ModuleHooks;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\ThemeSettings;
use Framelix\Myself\Utils\ModuleUtils;

use function class_exists;
use function end;
use function explode;
use function file_exists;
use function http_response_code;
use function md5;
use function strtolower;
use function trim;

use const FRAMELIX_MODULE;

/**
 * Index
 */
class Index extends LayoutView
{
    /**
     * Is in editmode
     * @var bool
     */
    public bool $editMode = false;

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
     * The current page
     * @var Page|null
     */
    protected ?Page $page = null;

    /**
     * The current theme
     * @var ThemeSettings|null
     */
    protected ?ThemeSettings $themeSettings = null;

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getGet('editMode') && !User::get()) {
            View::getUrl(View\Backend\Login::class)
                ->setParameter('redirect', Url::create())
                ->redirect();
        }
        $applicationUrl = Url::getApplicationUrl();
        $url = Url::create();
        $relativeUrl = trim($url->getRelativePath($applicationUrl), "/");
        $this->page = $this->page ?? Page::getByConditionOne('url = {0}', [$relativeUrl]);
        if (($this->page->flagDraft ?? null) && !LayoutUtils::isEditAllowed()) {
            $this->page = null;
        }
        $this->pageTitle = '__myself_modulename__';
        if ($this->page) {
            if (LayoutUtils::isEditAllowed() && Request::getGet('editMode')) {
                $this->editMode = true;
            }
            $this->pageTitle = $this->page->title;
            $this->themeSettings = $this->page->getThemeSettings();
            if ($this->page->password && Form::isFormSubmitted('pagepassword') && Request::getPost('password')) {
                if (Request::getPost('password') === $this->page->password) {
                    Session::set('myself-page-password-' . md5($this->page->password), true);
                    Toast::success('__myself_page_password_success__');
                } else {
                    Toast::error('__myself_page_password_wrong__');
                }
                Url::create()->redirect();
            }
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if (!$this->page) {
            http_response_code(404);
            echo '<div style="text-align: center"><div style="display: inline-block; padding:30px;
background: white; color:#222; font-weight: bold">' . Lang::get('__myself_page_not_exist__');
            $applicationUrl = Url::getApplicationUrl();
            $url = Url::create();
            $relativeUrl = trim($url->getRelativePath($applicationUrl), "/");
            if ($relativeUrl) {
                echo '<br/><a href="' . $applicationUrl . '">' . Lang::get('__myself_goback__') . '</a>';
            }
            echo '</div></div>';
            return;
        }
        $favicon = MediaFile::getById(\Framelix\Myself\Storable\WebsiteSettings::get('favicon'));
        $imageData = $favicon?->getImageData();
        if ($imageData) {
            $this->addHeadHtml('<link rel="icon" href="' . $imageData['sizes']['original']['url'] . '">');
        } else {
            $this->addHeadHtml(
                '<link rel="icon" href="' . Url::getUrlToFile(__DIR__ . "/../../public/img/logo-squared.svg") . '">'
            );
        }
        if ($settingValue = \Framelix\Myself\Storable\WebsiteSettings::get('og_site_name')) {
            $this->addHeadHtml('<meta property="og:site_name" content="' . HtmlUtils::escape($settingValue) . '"/>');
        }
        if ($settingValue = \Framelix\Myself\Storable\WebsiteSettings::get('og_image')) {
            $imageData = MediaFile::getById($settingValue)?->getImageData();
            if ($imageData) {
                $this->addHeadHtml(
                    '<meta property="og:image" content="' . $imageData['sizes']['original']['url'] . '"/>'
                );
            }
        }
        if ($settingValue = \Framelix\Myself\Storable\WebsiteSettings::get('og_title')) {
            $this->addHeadHtml('<meta property="og:title" content="' . HtmlUtils::escape($settingValue) . '"/>');
        }
        if ($settingValue = \Framelix\Myself\Storable\WebsiteSettings::get('og_description')) {
            $this->addHeadHtml('<meta property="og:description" content="' . HtmlUtils::escape($settingValue) . '"/>');
        }
        if ($settingValue = \Framelix\Myself\Storable\WebsiteSettings::get('author')) {
            $this->addHeadHtml('<meta property="author" content="' . HtmlUtils::escape($settingValue) . '"/>');
        }
        if ($settingValue = \Framelix\Myself\Storable\WebsiteSettings::get('keywords')) {
            $this->addHeadHtml('<meta property="keywords" content="' . HtmlUtils::escape($settingValue) . '"/>');
        }
        $this->addHeadHtml(
            '
            <meta property="og:type" content="website" />
            <meta property="og:url" content="' . Url::create() . '" />
            <meta name="generator" content="PageMyself Website Builder" />
        '
        );
        if (!$this->editMode) {
            $pageCss = \Framelix\Myself\Storable\WebsiteSettings::get('pagecss');
            if ($pageCss) {
                $this->addHeadHtml('<style>' . $pageCss . '</style>');
            }
            if ($settingValue = \Framelix\Myself\Storable\WebsiteSettings::get('headHtml')) {
                $this->addHeadHtml($settingValue);
            }
        }
        $themeBlock = $this->page->getThemeBlock();
        $fontUrls = $themeBlock->themeSettings->settings['fontUrls'] ?? null;
        if ($fontUrls) {
            $this->addHeadHtml(
                '<script>Myself.parseCustomFonts(' . JsonUtils::encode($fontUrls) . ')</script>'
            );
        }
        $defaultFont = $themeBlock->themeSettings->settings['defaultFont'] ?? null;
        if ($defaultFont) {
            $this->addHeadHtml(
                '<style>.framelix-page{font-family:' . $defaultFont . ', Arial, sans-serif;}</style>'
            );
        }
        ModuleHooks::callHook('beforeViewShowContent', [$this]);
        $themeBlock->viewSetup($this);
        $themeBlock->showLayout($this);
    }

    /**
     * Show content with page layout
     * @return void
     */
    public function showContentWithLayout(): void
    {
        $this->includeResources();
        Buffer::start();
        if ($this->contentCallable) {
            call_user_func_array($this->contentCallable, []);
        } else {
            $this->showContent();
        }
        $pageContent = Buffer::getAll();

        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->set('data-view', get_class(self::$activeView));
        $htmlAttributes->set('data-edit', $this->editMode ? '1' : '0');
        $htmlAttributes->set('data-page', $this->page);
        $htmlAttributes->set('data-mobile', Request::getGet('mobile') ? '1' : '0');

        if ($this->editMode) {
            $config = [
                'tinymceUrl' => Url::getUrlToFile(__DIR__ . "/../../../Framelix/public/vendor/tinymce/tinymce.min.js"),
                'pageBlockEditUrl' => View::getUrl(PageBlockEdit::class),
                'websiteSettingsEditUrl' => View::getUrl(WebsiteSettings::class),
                'blockLayoutApiUrl' => JsCall::getCallUrl(BlockLayoutEditor::class, '')
            ];
            $this->addHeadHtml('<script>MyselfEdit.config = ' . JsonUtils::encode($config) . '</script>');
        }
        Buffer::start();
        echo '<!DOCTYPE html>';
        echo '<html lang="' . ($this->page?->lang ?? Lang::$lang) . '" ' . $htmlAttributes . ' data-color-scheme-force="light">';
        $this->showDefaultPageStartHtml();
        echo '<body>';
        ModuleHooks::callHook('afterBodyTagOpened', [$this]);
        echo '<div class="framelix-page">';
        if ($this->editMode) {
            ?>
            <div class="myself-edit-frame">
                <div class="myself-edit-frame-outer-top">
                    <div class="myself-edit-frame-outer-margin">
                        <a href="<?= Url::getBrowserUrl() ?>"><img
                                    src="<?= Url::getUrlToFile("img/logo-colored-white.svg") ?>"
                                    alt="" height="30"></a>
                        <button
                                class="framelix-button framelix-button-primary framelix-button-small myself-open-website-settings"
                                data-icon-left="language"><?= Lang::get('__myself_websitesettings__') ?></button>
                        <a href="<?= Url::create()->setParameter('mobile', Request::getGet('mobile') ? 0 : 1) ?>"
                           class="framelix-button framelix-button-small"
                           data-icon-left="devices" title="__myself_toggle_mobile__"></a>
                        <a href="<?= Url::create()->removeParameter('editMode') ?>"
                           class="framelix-button framelix-button-small"
                           data-icon-left="highlight_off" title="__myself_disable_editmode__"></a>
                    </div>
                </div>
                <div class="myself-edit-frame-outer-bottom">
                    <div class="myself-edit-frame-outer-margin">
                        <a href="<?= View::getUrl(Backend\Index::class) ?>"
                           class="framelix-button framelix-button-small framelix-button-primary"
                           data-icon-left="link" target="_blank"><?= Lang::get('__myself_goto_backend__') ?></a>
                        <?php
                        if (file_exists(AppUpdate::UPDATE_CACHE_FILE)) {
                            ?>
                            <a href="<?= View::getUrl(View\Backend\AppUpdate::class) ?>"
                               class="framelix-button framelix-button-small framelix-button-error"
                               data-icon-left="link" target="_blank"><?= Lang::get(
                                    '__myself_appupdate_available__'
                                ) ?></a>
                            <?php
                        }
                        if (file_exists(ModuleUtils::MODULE_UPDATE_CACHE_FILE)) {
                            ?>
                            <a href="<?= View::getUrl(Backend\Modules\Index::class) ?>"
                               class="framelix-button framelix-button-small framelix-button-error"
                               data-icon-left="link" target="_blank"><?= Lang::get(
                                    '__myself_moduleupdate_available__'
                                ) ?></a>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="myself-edit-frame-outer-left">
                    <div class="myself-edit-frame-outer-margin"></div>
                </div>
                <div class="myself-edit-frame-outer-right">
                    <div class="myself-edit-frame-outer-margin">
                        <div class="myself-edit-frame-button-row">
                            <button class="framelix-button framelix-button-primary myself-open-layout-block-editor"
                                    data-icon-left="grid_view"
                                    title="__myself_blocklayout_openeditor__"
                                    data-page-id="<?= $this->page ?>"></button>
                        </div>
                    </div>
                </div>
                <div class="myself-edit-frame-inner">
                    <iframe src="<?= Url::create()->removeParameter('editMode') ?>"></iframe>
                </div>
            </div>
            <?php
        } elseif (
            ($this->page->password ?? null)
            && !Session::get('myself-page-password-' . md5($this->page->password))
        ) {
            $form = new Form();
            $form->id = "pagepassword";
            $form->submitAsync = false;
            $form->submitWithEnter = true;

            $field = new Password();
            $field->name = "password";
            $field->label = "__myself_page_password__";
            $form->addField($field);

            $form->addSubmitButton('login', '__myself_page_login__');
            $form->show();
        } else {
            echo $pageContent;
        }
        echo '</div>';
        ?>
        <script>
          Framelix.initLate()
        </script>
        <?php
        if (!$this->editMode) {
            $pageJs = \Framelix\Myself\Storable\WebsiteSettings::get('pagejs');
            if ($pageJs) {
                echo '<script>try{' . $pageJs . '}catch (e){console.error(e)}</script>';
            }
        }
        ModuleHooks::callHook('beforeBodyTagClosed', [$this]);
        echo '</body></html>';
        Buffer::flush();
    }

    /**
     * Include all required page resources
     * @return void
     */
    private function includeResources(): void
    {
        Compiler::compile("Framelix");
        Compiler::compile(FRAMELIX_MODULE);
        $this->includeCompiledFilesForModule("Framelix");
        $this->includeCompiledFilesForModule(FRAMELIX_MODULE);
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "myself");
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "myself");

        if ($this->editMode) {
            $this->includeCompiledFile("Framelix", "scss", "backend-fonts");
            $this->includeCompiledFile(FRAMELIX_MODULE, "js", "myself-edit");
            $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "myself-edit");
        } else {
            if (!$this->page) {
                return;
            }
            $themeClassName = $this->page->getThemeClass();
            $themeExp = explode("\\", $themeClassName);
            $themeName = strtolower($themeExp[3]);
            $themeModule = $themeExp[1];
            Compiler::compile($themeModule);

            $configKey = "theme-" . $themeName;
            if (Config::get('compiler[' . $themeModule . '][js][' . $configKey . ']')) {
                $this->includeCompiledFile($themeModule, "js", $configKey);
            }
            if (Config::get('compiler[' . $themeModule . '][scss][' . $configKey . ']')) {
                $this->includeCompiledFile($themeModule, "scss", $configKey);
            }

            $pageBlocks = ArrayUtils::merge(
                $this->page->getPageBlocks(!$this->editMode),
                $this->themeSettings->getFixedPageBlocks()
            );
            $pageBlockClasses = [];
            foreach ($pageBlocks as $pageBlock) {
                if (!class_exists($pageBlock->pageBlockClass)) {
                    continue;
                }
                $pageBlockClasses[$pageBlock->pageBlockClass] = $pageBlock->pageBlockClass;
            }
            foreach ($pageBlockClasses as $pageBlockClass) {
                $pageBlockExp = explode("\\", $pageBlockClass);
                $blockName = strtolower(end($pageBlockExp));
                $blockModule = $pageBlockExp[1];
                $configKey = "pageblock-" . $blockName;
                Compiler::compile($blockModule);
                if (Config::get('compiler[' . $blockModule . '][js][' . $configKey . ']')) {
                    $this->includeCompiledFile($blockModule, "js", $configKey);
                }
                if (Config::get('compiler[' . $blockModule . '][scss][' . $configKey . ']')) {
                    $this->includeCompiledFile($blockModule, "scss", $configKey);
                }
            }
        }
    }

}