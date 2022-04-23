<?php

namespace Framelix\PageMyself\View\Backend\PageEditor;

use Framelix\Framelix\Form\Field\Editor;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\ColorName;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Component\ComponentBase;
use Framelix\PageMyself\Form\Field\MediaBrowser;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\ThemeBase;

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
        if ($jsCall->action === 'componentApiRequest') {
            $componentBlock = ComponentBlock::getById($jsCall->parameters['blockId'] ?? null);
            if (!$componentBlock) {
                return;
            }
            $params = $jsCall->parameters['params'] ?? null;
            switch ($jsCall->parameters['action']) {
                case 'saveText':
                    $settings = $componentBlock->settings;
                    $settings['text'][$params['id']] = $params['text'];
                    $componentBlock->settings = $settings;
                    $componentBlock->store();
                    break;
                case 'mediaBrowser':
                    $jsCall = new JsCall('browser', ['action' => 'browser']);
                    echo $jsCall->call(MediaBrowser::class . "::onJsCall");
                    break;
                case 'editorPredefinedLayouts':
                    // todo blocks
                    ?>
                    sdf
                    <?php
                    break;
            }
            return;
        }
        $page = Page::getById($jsCall->parameters['page'] ?? null);
        switch ($jsCall->parameters['action'] ?? null) {
            case 'pageData':
                $jsCall->result = [
                    'theme' => $page->getThemeInstance()->getThemeId()
                ];
                break;
            case 'changeTheme':
                $page->theme = $jsCall->parameters['theme'];
                $page->store();
                break;
            case 'getComponentList':
                $jsCall->result = ComponentBase::getAvailableList();
                break;
            case 'getComponentBlockInfos':
                $blockList = ComponentBase::getAvailableList();
                $blocks = ComponentBlock::getByIds($jsCall->parameters['blockIds']);
                $arr = [];
                foreach ($blocks as $componentBlock) {
                    $arr[$componentBlock->id] = [
                        'id' => $componentBlock->id,
                        'title' => $blockList[$componentBlock->blockClass]['title'],
                        'help' => $blockList[$componentBlock->blockClass]['help'],
                    ];
                }
                $jsCall->result = $arr;
                break;
            case 'updateBlockSort':
                $blockA = ComponentBlock::getById($jsCall->parameters['blockA'] ?? null);
                $blockB = ComponentBlock::getById($jsCall->parameters['blockB'] ?? null);
                if ($blockA && $blockB) {
                    $sortA = $blockA->sort;
                    $sortB = $blockB->sort;
                    $blockA->sort = $sortB;
                    $blockB->sort = $sortA;
                    $blockA->preserveUpdateUserAndTime();
                    $blockA->store();
                    $blockB->preserveUpdateUserAndTime();
                    $blockB->store();
                }
                break;
            case 'blockSettings':
                $componentBlock = ComponentBlock::getById($jsCall->parameters['block'] ?? null);
                if ($componentBlock) {
                    $list = ComponentBase::getAvailableList();
                    $listRow = $list[$componentBlock->blockClass];

                    $instance = ComponentBase::createInstance($componentBlock);
                    $form = new Form();

                    $field = new Hidden();
                    $field->name = "componentBlockId";
                    $field->defaultValue = $componentBlock;
                    $form->addField($field);

                    $field = new Html();
                    $field->name = "_desc";
                    $field->label = $listRow['title'];
                    $field->labelDescription = $listRow['desc'];
                    $form->addField($field);

                    if ($listRow['help'] ?? null) {
                        $field = new Html();
                        $field->name = "_help";
                        $field->defaultValue = '<div class="framelix-alert">' . Lang::get($listRow['help']) . '</div>';
                        $form->addField($field);
                    }

                    $fieldsDefault = count($form->fields);
                    $instance->addSettingFields($form);
                    $fieldsAll = count($form->fields);

                    if ($fieldsAll !== $fieldsDefault) {
                        $form->addSubmitButton();
                    }
                    $form->addButton('delete-block', '__pagemyself_component_delete__', 'delete', ColorName::ERROR);
                    $form->show();
                }
                break;
            case 'createComponentBlock':
                $bellow = ComponentBlock::getById($jsCall->parameters['bellow'] ?? null);
                $componentBlock = new ComponentBlock();
                $componentBlock->page = $page;
                $componentBlock->blockClass = $jsCall->parameters['blockClass'];
                $componentBlock->placement = $bellow->placement ?? $jsCall->parameters['placement'];
                $componentBlock->sort = ($bellow->sort ?? -1);
                $blockInstance = ComponentBase::createInstance($componentBlock);
                $componentBlock->settings = $blockInstance->getDefaultSettings();
                $componentBlock->store();
                $blocks = $page->getComponentBlocks();
                $sort = 0;
                // resort blocks
                foreach ($blocks as $componentBlock) {
                    $componentBlock->sort = $sort++;
                    $componentBlock->preserveUpdateUserAndTime();
                    $componentBlock->store();
                }
                $jsCall->result = [
                    'url' => $componentBlock->getPublicUrl()
                ];
                break;
            case 'deleteBlock':
                ComponentBlock::getById($jsCall->parameters['blockId'])?->delete();
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "pageeditor");
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "pageeditor");
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show the page content
     */
    public function showContent(): void
    {
        // call defaults will initialize them when not yet added
        // useful because this is the first page the user will open after setup
        Page::getDefault();
        $config = [
            'apiRequestUrl' => JsCall::getCallUrl(__CLASS__, 'componentApiRequest'),
            'tinymceUrl' => Url::getUrlToFile(Editor::TINYMCE_PATH, antiCacheParameter: false),
            'tinymcePluginsUrl' => Compiler::getDistUrl(FRAMELIX_MODULE, 'js', 'tinymce-plugins')
        ];
        ?>
        <div data-color-scheme="dark" class="pageeditor-frame"
             data-edit-url="<?= JsCall::getCallUrl(__CLASS__, 'custom') ?>">
            <div class="pageeditor-frame-top">
                <div class="pageeditor-frame-top-addressbar">
                    <button class="framelix-button hide-if-no-page" data-icon-left="chevron_left"
                            title="__pagemyself_pageeditor_page_back__" data-frame-action="back"></button>
                    <button class="framelix-button hide-if-no-page" data-icon-left="autorenew"
                            title="__pagemyself_pageeditor_page_reload__" data-frame-action="reload"></button>
                    <button class="framelix-button hide-if-no-page" data-icon-left="home"
                            title="__pagemyself_pageeditor_page_home__" data-frame-action="loadurl"
                            data-url="<?= Url::getApplicationUrl() ?>"></button>
                    <div class="hide-if-no-page">
                        <?php
                        $field = new Select();
                        $field->name = 'jumpToPage';
                        $field->chooseOptionLabel = '__pagemyself_pageeditor_jump_to_page__';
                        $pages = Page::getByCondition(sort: "+sort");
                        foreach ($pages as $page) {
                            $field->addOption($page->getPublicUrl(), $page->title);
                        }
                        $field->show();
                        ?>
                    </div>
                    <div class="pageeditor-address"></div>
                    <button class="framelix-button hide-if-no-page" data-icon-left="smartphone"
                            title="__pagemyself_pageeditor_page_mobile__" data-frame-action="mobile"></button>
                </div>

                <?php
                $field = new Select();
                $field->name = 'theme';
                $field->chooseOptionLabel = '__pagemyself_theme__';
                $field->showResetButton = false;
                $themes = ThemeBase::getAvailableList();
                foreach ($themes as $themeId => $row) {
                    $field->addOption(
                        $themeId,
                        Lang::get('__pagemyself_theme__') . ": " . $themeId
                    );
                }
                $field->defaultValue = 'Hello';
                $field->show();
                ?>
                <?= Lang::get('__pagemyself_pagetitle__') ?>:
                <span class="pageeditor-frame-top-title"></span>
            </div>
            <iframe src="<?= \Framelix\Framelix\View::getUrl(\Framelix\PageMyself\View\Index::class) ?>" width="100%"
                    frameborder="0"></iframe>
        </div>
        <script>
          PageMyselfPageEditor.config = <?=JsonUtils::encode($config)?>;
        </script>
        <?php
    }
}