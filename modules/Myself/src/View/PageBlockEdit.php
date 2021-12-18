<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;

use function array_unshift;
use function preg_replace;
use function str_replace;
use function strtolower;
use function var_dump;

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
                echo View\Api::getSignedCallPhpMethodUrlString(
                    MediaBrowser::class,
                    'list',
                    [
                        'allowedExtensions' => Request::getGet('allowedExtensions')
                    ]
                );
                break;
            case 'moveup':
            case 'movedown':
                if ($action === 'movedown') {
                    $pageBlockFlip = PageBlock::getByConditionOne(
                        'page = {0} && sort > {1}',
                        [$requestPage, $requestPageBlock->sort],
                        ['+sort']
                    );
                } else {
                    $pageBlockFlip = PageBlock::getByConditionOne(
                        'page = {0} && sort < {1}',
                        [$requestPage, $requestPageBlock->sort],
                        ['-sort']
                    );
                }
                if ($pageBlockFlip) {
                    $prevSort = $requestPageBlock->sort;
                    $requestPageBlock->sort = $pageBlockFlip->sort;
                    $requestPageBlock->store();
                    $pageBlockFlip->sort = $prevSort;
                    $pageBlockFlip->store();
                }
                $pageBlocks = PageBlock::getByCondition(
                    'page = {0}',
                    [$requestPage],
                    ['+sort']
                );
                // set all sort flags ascending
                $sort = 0;
                foreach ($pageBlocks as $pageBlock) {
                    $pageBlock->sort = $sort++;
                    $pageBlock->store();
                }
                Toast::success('__myself_pageblock_deleted__');
                Url::getBrowserUrl()->redirect();
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
            case 'edit':
                if ($action === 'create') {
                    $pageBlockLast = PageBlock::getByConditionOne('page = {0}', [$requestPage], ['-sort']);
                    $useSort = $pageBlockLast->sort ?? 0;
                    $pageBlock = new PageBlock();
                    $pageBlock->page = $requestPage;
                    $pageBlock->sort = $useSort + 1;
                    $pageBlock->flagDraft = true;
                    $pageBlock->pageBlockClass = $requestPageBlockClass;
                    $pageBlock->store();
                    $requestPageBlock = $pageBlock;
                }
                $block = $requestPageBlock->getLayoutBlock();
                $forms = $block->getSettingsForms();

                echo '<div class="myself-page-block-edit-tabs">';
                $tabs = new Tabs();
                $tabs->id = "pageblock-" . $requestPageBlock->id;
                foreach ($forms as $key => $form) {
                    $form->submitUrl = Url::create()
                        ->setParameter('formKey', $key)
                        ->setParameter('action', 'save-settings')
                        ->setParameter('pageBlockId', $requestPageBlock);
                    $form->id = $form->id ?? $key;
                    foreach ($form->fields as $field) {
                        $keyParts = ArrayUtils::splitKeyString($field->name);
                        if ($field->label === null) {
                            $field->label = ClassUtils::getLangKey(
                                $requestPageBlock->pageBlockClass,
                                $field->name
                            );
                            $field->label = preg_replace("~\[(.*?)\]~", "_$1", $field->label);
                            $field->label = str_replace("_pageblocksettings", "", $field->label);
                        }
                        if ($field->labelDescription === null) {
                            $langKey = ClassUtils::getLangKey(
                                $requestPageBlock->pageBlockClass,
                                $field->name . "_desc"
                            );
                            $langKey = preg_replace("~\[(.*?)\]~", "_$1", $langKey);
                            $langKey = str_replace("_pageblocksettings", "", $langKey);
                            if (Lang::keyExist($langKey)) {
                                $field->labelDescription = Lang::get($langKey);
                            }
                        }
                        $field->defaultValue = ArrayUtils::getValue(
                                $requestPageBlock,
                                $keyParts
                            ) ?? $field->defaultValue;
                        array_unshift($keyParts, "pageBlockSettings");
                    }
                    $label = $form->label ?? ClassUtils::getLangKey(
                            $requestPageBlock->pageBlockClass,
                            "form_" . $form->id
                        );
                    $form->label = $label;
                    Buffer::start();
                    $form->addSubmitButton('save', '__save__', 'save');
                    $block->showSettingsForm($form);
                    $content = Buffer::get();
                    $tabs->addTab($form->id, $label, $content);
                }
                $tabs->show();
                echo '</div>';
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
                    $html .= '<div class="myself-page-block-create-entry myself-page-api-call framelix-space-click"  tabindex="0" data-action="create" data-page-block-class="' . $blockClass . '" data-page-id="' . $requestPage . '" ' . $style . '>
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