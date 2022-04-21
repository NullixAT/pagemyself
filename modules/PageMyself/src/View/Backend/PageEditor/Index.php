<?php

namespace Framelix\PageMyself\View\Backend\PageEditor;

use Framelix\Framelix\Form\Field\Editor;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\PageBlock\Base;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\Storable\PageBlock;
use Framelix\PageMyself\Storable\PageLayout;

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
        if ($jsCall->action === 'pageBlockApiRequest') {
            $pageBlock = PageBlock::getById($jsCall->parameters['blockId'] ?? null);
            if (!$pageBlock) {
                return;
            }
            $params = $jsCall->parameters['params'] ?? null;
            switch ($jsCall->parameters['action']) {
                case 'save-text':
                    $settings = $pageBlock->settings;
                    $settings['text'][$params['id']] = $params['text'];
                    $pageBlock->settings = $settings;
                    $pageBlock->store();
                    break;
            }
            return;
        }
        $page = Page::getById($jsCall->parameters['page'] ?? null);
        switch ($jsCall->parameters['action'] ?? null) {
            case 'pageData':
                $jsCall->result = [
                    'layout' => $page->layout ?? PageLayout::getDefault(),
                    'design' => $page->design ?? 'default'
                ];
                break;
            case 'changeLayout':
                $layout = PageLayout::getById($jsCall->parameters['layout'] ?? null);
                if ($layout) {
                    $page->layout = $layout->flagDefault ? null : $layout;
                    $page->store();
                }
                break;
            case 'getPageBlockList':
                $jsCall->result = Base::getAvailableList();
                break;
            case 'getPageBlockInfos':
                $blockList = Base::getAvailableList();
                $blocks = PageBlock::getByIds($jsCall->parameters['blockIds']);
                $arr = [];
                foreach ($blocks as $block) {
                    $arr[$block->id] = [
                        'id' => $block->id,
                        'title' => $blockList[$block->blockClass]['title'],
                        'help' => $blockList[$block->blockClass]['help'],
                    ];
                }
                $jsCall->result = $arr;
                break;
            case 'updateBlockSort':
                $blockA = PageBlock::getById($jsCall->parameters['blockA'] ?? null);
                $blockB = PageBlock::getById($jsCall->parameters['blockB'] ?? null);
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
            case 'createNewPageBlock':
                $bellow = PageBlock::getById($jsCall->parameters['bellow'] ?? null);
                $pageBlock = new PageBlock();
                $pageBlock->page = $page;
                $pageBlock->blockClass = $jsCall->parameters['blockClass'];
                $pageBlock->placement = $bellow->placement ?? $jsCall->parameters['placement'];
                $pageBlock->sort = ($bellow->sort ?? -1);
                $blockInstance = Base::createInstance($pageBlock);
                $pageBlock->settings = $blockInstance->getDefaultSettings();
                $pageBlock->store();
                $blocks = $page->getPageBlocks();
                $sort = 0;
                // resort blocks
                foreach ($blocks as $block) {
                    $block->sort = $sort++;
                    $block->preserveUpdateUserAndTime();
                    $block->store();
                }
                $jsCall->result = [
                    'url' => $pageBlock->getPublicUrl()
                ];
                break;
            case 'deleteBlock':
                PageBlock::getById($jsCall->parameters['blockId'])?->delete();
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
        PageLayout::getDefault();
        Page::getDefault();
        $config = [
            'apiRequestUrl' => JsCall::getCallUrl(__CLASS__, 'pageBlockApiRequest'),
            'tinymceUrl' => Url::getUrlToFile(Editor::TINYMCE_PATH, antiCacheParameter: false),
            'tinymcePluginsUrl' => Compiler::getDistUrl(FRAMELIX_MODULE, 'js', 'tinymce-plugins')
        ];
        ?>
        <div data-color-scheme="dark" class="pageeditor-frame"
             data-edit-url="<?= JsCall::getCallUrl(__CLASS__, 'custom') ?>">
            <div class="pageeditor-frame-top">
                <div class="pageeditor-frame-top-addressbar">
                    <a href="<?= \Framelix\Framelix\View::getUrl(
                        \Framelix\PageMyself\View\Backend\PageLayout\Index::class
                    ) ?>"
                       class="framelix-button hide-if-no-page"
                       data-icon-left="grid_view"
                       title="__pagemyself_pageeditor_manage_layout__"></a>
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
                $field->name = 'pageLayout';
                $field->chooseOptionLabel = '__pagemyself_pageeditor_choose_layout__';
                $field->showResetButton = false;
                $layouts = PageLayout::getByCondition(sort: ["-flagDefault", "+title"]);
                foreach ($layouts as $layout) {
                    $field->addOption(
                        $layout->id,
                        Lang::get('__pagemyself_pageeditor_choose_layout__') . ": " . $layout->title
                    );
                    if ($layout->flagDefault) {
                        $field->defaultValue = $layout;
                    }
                }
                $field->show();
                ?>
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