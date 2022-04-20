<?php

namespace Framelix\PageMyself\View\Backend\PageEditor;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\PageBlock\Base;
use Framelix\PageMyself\Storable\Page;
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
            case 'newPageBlock':
                $jsCall->result = Base::getAvailableList();
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
        <?php
    }
}