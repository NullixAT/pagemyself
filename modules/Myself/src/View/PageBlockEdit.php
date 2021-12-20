<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;

use function strtolower;

/**
 * Index
 */
class PageBlockEdit extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,content";

    /**
     * On request
     */
    public function onRequest(): void
    {
        $requestPage = Page::getById(Request::getGet('pageId'));
        $requestPageBlock = PageBlock::getById(Request::getGet('pageBlockId'));
        if ($requestPageBlock) {
            $requestPage = $requestPageBlock->page;
        }
        $requestPageBlockClass = Request::getGet('pageBlockClass');
        $action = Request::getGet('action');
        switch ($action) {
            case 'getmediabrowserurl':
                echo JsCall::getCallUrl(
                    MediaBrowser::class,
                    'list',
                    [
                        'allowedExtensions' => Request::getGet('allowedExtensions')
                    ]
                );
                break;
            case 'delete':
                $requestPageBlock?->delete();
                Toast::success('__myself_pageblock_deleted__');
                Url::getBrowserUrl()->redirect();
            case 'save-editable-content':
                $storable = Storable::getById(Request::getPost('storableId'));
                if ($storable) {
                    if (Request::getPost('arrayKey') === null || Request::getPost('arrayKey') === '') {
                        $storable->{Request::getPost('propertyName')} = Request::getPost('content');
                    } else {
                        $arr = $storable->{Request::getPost('propertyName')} ?? [];
                        ArrayUtils::setValue($arr, Request::getPost('arrayKey'), Request::getPost('content'));
                        $storable->{Request::getPost('propertyName')} = $arr;
                    }
                    $storable->store();
                    echo 1;
                    return;
                }
                echo 0;
                break;
            case 'save-settings':
                $block = $requestPageBlock->getLayoutBlock();
                $forms = $block->getSettingsForms();
                $form = $forms[Request::getGet('formKey')];
                $block->setValuesFromSettingsForm($form);
                $requestPageBlock->store();
                Toast::success('__saved__');
                Response::showFormAsyncSubmitResponse();
            case 'create':
                $pageBlock = new PageBlock();
                $pageBlock->page = $requestPage;
                $pageBlock->flagDraft = false;
                $pageBlock->pageBlockClass = $requestPageBlockClass;
                $pageBlock->store();
                Toast::success('__myself_pageblock_created__');
                break;
            case 'select-new':
                ?>
                <div class="framelix-alert framelix-alert-primary"><?= Lang::get(
                        '__myself_pageblock_create_attention__'
                    ) ?></div>
                <?php
                $form = new Form();
                $form->id = "create-page-block";

                $field = new Html();
                $field->name = "html";
                $form->addField($field);

                $html = '<div class="myself-page-block-create-entries">';
                $blockClasses = BlockBase::getAllClasses();
                foreach ($blockClasses as $blockClass) {
                    $blockModule = ClassUtils::getModuleForClass($blockClass);
                    $blockName = strtolower(ClassUtils::getClassBaseName($blockClass));
                    $title = '<div>' . Lang::get(ClassUtils::getLangKey($blockClass)) . '</div>';
                    $descKey = ClassUtils::getLangKey($blockClass, "desc");
                    $desc = null;
                    if (Lang::keyExist($descKey)) {
                        $desc = '<div class="myself-page-block-create-select-desc">' . Lang::get($descKey) . '</div>';
                    }
                    $iconUrl = Url::getUrlToFile(
                        FileUtils::getModuleRootPath($blockModule) . "/public/page-blocks/$blockName/icon.png"
                    );
                    $style = '';
                    if ($iconUrl) {
                        $title = '<div style="background-image:url(' . $iconUrl . ')"></div>' . $title;
                    }
                    $html .= '<div class="myself-page-block-create-entry myself-create-new-page-block framelix-space-click" tabindex="0" data-page-block-class="' . $blockClass . '" data-page-id="' . $requestPage . '" ' . $style . '>
                        <div class="myself-page-block-create-select-title">' . $title . '</div>
                        ' . $desc . '                        
                    </div>';
                }
                $html .= '</div>';
                $field->defaultValue = $html;

                $form->show();
                break;
        }
    }
}