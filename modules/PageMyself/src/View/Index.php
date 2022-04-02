<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View\LayoutView;
use Framelix\PageMyself\ModuleHooks;
use Framelix\PageMyself\Storable\Page;

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
     * On request
     */
    public function onRequest(): void
    {
        $applicationUrl = Url::getApplicationUrl();
        $url = Url::create();
        $relativeUrl = trim($url->getRelativePath($applicationUrl), "/");
        $this->page = $this->page ?? Page::getByConditionOne('url = {0}', [$relativeUrl]);
        if (($this->page->flagDraft ?? null) && !User::get()) {
            $this->page = null;
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
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "pagemyself");

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
        if (
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

            $form->addSubmitButton('login', '__pagemyself_page_login__');
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
            echo '<div style="text-align: center"><div style="display: inline-block; padding:30px;
background: white; color:#222; font-weight: bold">' . Lang::get('__pagemyself_page_not_exist__');
            echo '</div></div>';
            return;
        }
    }
}