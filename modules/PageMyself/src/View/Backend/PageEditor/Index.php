<?php

namespace Framelix\PageMyself\View\Backend\PageEditor;

use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Storable\Page;

/**
 * Index
 */
class Index extends View
{

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        $page = Page::getById($jsCall->parameters['page'] ?? null);
        switch ($jsCall->action) {
            case 'pagelayout':
                if (!$page) {
                    echo Lang::get('__pagemyself_page_required_');
                    return;
                }
                $form = new Form();
                $form->id = "pagelayout";

                $field = new Hidden();
                $field->name = "page";
                $field->defaultValue = $page;
                $form->addField($field);

                $pages = Page::getByCondition(sort: '+sort');
                if ($pages) {
                    $field = new Select();
                    $field->name = "copyFrom";
                    $field->label = "__pagemyself_editor_pagelayout_copyfrom__";
                    $field->defaultValue = $page->layoutSettings[$field->name] ?? '';
                    foreach ($pages as $page) {
                        if (!($page->layoutSettings['copyFrom'] ?? null)) {
                            continue;
                        }
                        $field->addOption($page, $page->title);
                    }
                    if ($field->getOptions()) {
                        $form->addField($field);
                    }
                }

                $field = new Select();
                $field->name = "design";
                $field->label = "__pagemyself_editor_pagelayout_design__";
                $field->required = true;
                $field->addOption('default', '__pagemyself_editor_pagelayout_design_default__');
                $field->addOption('dark', '__pagemyself_editor_pagelayout_design_dark__');
                $field->addOption('custom', '__pagemyself_editor_pagelayout_design_custom__');
                $field->defaultValue = $page->layoutSettings[$field->name] ?? 'default';
                $field->getVisibilityCondition()->empty("copyFrom");
                $form->addField($field);

                $field = new Select();
                $field->name = "align";
                $field->label = "__pagemyself_editor_pagelayout_align__";
                $field->required = true;
                $field->addOption('left', '__pagemyself_editor_pagelayout_align_left__');
                $field->addOption('center', '__pagemyself_editor_pagelayout_align_center__');
                $field->defaultValue = $page->layoutSettings[$field->name] ?? 'center';
                $field->getVisibilityCondition()->empty("copyFrom");
                $form->addField($field);

                $field = new Select();
                $field->name = "nav";
                $field->label = "__pagemyself_editor_pagelayout_nav__";
                $field->required = true;
                $field->addOption('none', '__pagemyself_editor_pagelayout_nav_none__');
                $field->addOption('top', '__pagemyself_editor_pagelayout_nav_top__');
                $field->addOption('left', '__pagemyself_editor_pagelayout_nav_left__');
                $field->defaultValue = $page->layoutSettings[$field->name] ?? 'top';
                $field->getVisibilityCondition()->empty("copyFrom");
                $form->addField($field);

                $field = new Number();
                $field->name = "maxWidth";
                $field->min = 400;
                $field->max = 10000;
                $field->setIntegerOnly();
                $field->required = true;
                $field->label = "__pagemyself_editor_pagelayout_maxwidth__";
                $field->defaultValue = $page->layoutSettings[$field->name] ?? 900;
                $field->getVisibilityCondition()->empty("copyFrom");
                $form->addField($field);

                $field = new Toggle();
                $field->name = "showNav";
                $field->label = "__pagemyself_editor_pagelayout_shownav__";
                $field->defaultValue = $page->layoutSettings[$field->name] ?? true;
                $field->getVisibilityCondition()->empty("copyFrom");
                $form->addField($field);

                $form->addSubmitButton();

                $form->show();
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getPost('framelix-form-pagelayout')) {
            $page = Page::getById(Request::getPost('page'));
            if ($page) {
                $copyFrom = Page::getById(Request::getPost('copyFrom'));
                if ($copyFrom) {
                    $page->layoutSettings = ['copyFrom' => $copyFrom];
                } else {
                    $settings = $_POST;
                    unset($settings['framelix-form-pagelayout'], $settings['framelix-form-button-save'], $settings['page'], $settings['copyFrom']);
                    $page->layoutSettings = $settings;
                }
                $page->store();
            }
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->redirect();
        }
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "pageeditor");
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "pageeditor");
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show the page content
     */
    public function showContent(): void
    {
        ?>
        <div class="pageeditor-frame">
            <div class="pageeditor-frame-top">
                <span class="framelix-button button-modal-call"
                      data-url="<?= JsCall::getCallUrl(__CLASS__, 'pagelayout') ?>">Edit Page Layout</span>
            </div>
            <iframe src="<?= \Framelix\Framelix\View::getUrl(\Framelix\PageMyself\View\Index::class) ?>" width="100%"
                    frameborder="0">

            </iframe>
            <div class="pageeditor-frame-bottom"></div>
        </div>
        <script>
          (function () {
            $('.button-modal-call').on('click', function () {
              FramelixModal.callPhpMethod($(this).attr('data-url'), { 'page': PageMyselfPageEditor.iframeHtml.attr('data-page') })
            })
          })()
        </script>
        <?php
    }
}