<?php

namespace Framelix\PageMyself\View\Backend\PageEditor;

use Framelix\Framelix\Form\Field\Editor;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\ColorName;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Component\ComponentBase;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\Storable\WebsiteSettings;
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
        $page = Page::getById($jsCall->parameters['page'] ?? null);
        switch ($jsCall->parameters['action'] ?? null) {
            case 'pageData':
                $jsCall->result = [
                    'theme' => $page->getThemeInstance()->themeId
                ];
                break;
            case 'changeTheme':
                $page->theme = $jsCall->parameters['theme'];
                $page->store();
                break;
            case 'themeSettings':
                $componentInstance = $page->getThemeInstance();
                echo '<h2>' . $componentInstance->themeId . ': ' . Lang::get(
                        '__pagemyself_pageeditor_theme_settings__'
                    ) . '</h2>';
                echo '<p class="framelix-alert">' . Lang::get('__pagemyself_pageeditor_theme_settings_desc__') . '</p>';
                $form = new Form();
                $form->id = "themeSettings";
                $form->submitUrl = Url::getBrowserUrl();

                $fieldsEditable = 0;
                $componentInstance->addThemeSettingFields($form);
                foreach ($form->fields as $field) {
                    if (!($field instanceof Hidden) && !($field instanceof Html)) {
                        $fieldsEditable++;
                    }
                    $field->defaultValue = WebsiteSettings::get(
                            'theme_' . $componentInstance->themeId . "_" . $field->name
                        ) ?? $field->defaultValue;
                    $keyPrefix = strtolower("__theme_" . $componentInstance->themeId . "_" . $field->name);
                    $field->label = Lang::get($field->label ?? $keyPrefix . "_label__");
                    if (Lang::keyExist($keyPrefix . "_desc__") && $field->labelDescription === null) {
                        $field->labelDescription = Lang::get($keyPrefix . "_desc__");
                    }
                }

                $field = new Hidden();
                $field->name = "page";
                $field->defaultValue = $page->id;
                $form->addField($field);

                if ($fieldsEditable) {
                    $form->addSubmitButton();
                }
                $form->show();
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
                    $themeInstance = $componentBlock->page->getThemeInstance();
                    $settings = $componentBlock->settings;
                    $list = ComponentBase::getAvailableList();
                    $listRow = $list[$componentBlock->blockClass];

                    $componentInstance = $componentBlock->getComponentInstance();
                    $form = new Form();
                    $form->id = "blockSettings";

                    $field = new Html();
                    $field->name = "_desc";
                    $field->label = $listRow['title'];
                    $field->labelDescription = $listRow['desc'];
                    $form->addField($field);

                    if ($listRow['help'] ?? null) {
                        $field = new Html();
                        $field->name = "_help";
                        $field->label = '';
                        $field->labelDescription = '';
                        $field->defaultValue = '<div class="framelix-alert">' . Lang::get($listRow['help']) . '</div>';
                        $form->addField($field);
                    }

                    $componentInstance->addSettingFields($form);
                    $themeInstance->addComponentSettingFields($form, $componentInstance);
                    $fieldsEditable = 0;
                    foreach ($form->fields as $field) {
                        if (!($field instanceof Hidden) && !($field instanceof Html)) {
                            $fieldsEditable++;
                        }
                        $field->defaultValue = $settings[$field->name] ?? $field->defaultValue;
                        $keyPrefix = strtolower("__theme_" . $themeInstance->themeId . "_" . $field->name);
                        $field->label = Lang::get($field->label ?? $keyPrefix . "_label__");
                        if (Lang::keyExist($keyPrefix . "_desc__") && $field->labelDescription === null) {
                            $field->labelDescription = Lang::get($keyPrefix . "_desc__");
                        }
                    }

                    $field = new Hidden();
                    $field->name = "componentBlockId";
                    $field->defaultValue = $componentBlock;
                    $form->addField($field);

                    if ($fieldsEditable) {
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
                $blockInstance = $componentBlock->getComponentInstance();
                $componentBlock->settings = $blockInstance->getDefaultSettings();
                $componentBlock->store();
                $blocks = $page->getComponentBlocks();
                $sort = 0;
                // resort blocks
                foreach ($blocks as $subBlock) {
                    $subBlock->sort = $sort++;
                    $subBlock->preserveUpdateUserAndTime();
                    $subBlock->store();
                }
                $jsCall->result = [
                    'url' => $componentBlock->getPublicUrl()
                ];
                break;
            case 'deleteBlock':
                ComponentBlock::getById($jsCall->parameters['componentBlockId'])?->delete();
                break;
            case 'getBlockSettingsList':
                $componentBlocks = $page->getComponentBlocks();
                if (!$componentBlocks) {
                    ?>
                    <div class="framelix-alert"><?= Lang::get('__pagemyself_component_no_blocks__') ?></div>
                    <?php
                    return;
                } ?>
                <div class="framelix-alert"><?= Lang::get('__pagemyself_component_ctrl_click__') ?></div>
                <?php

                $blockList = ComponentBase::getAvailableList();
                $tabs = new Tabs();
                /** @var ComponentBlock[][] $placements */
                $placements = [];
                foreach ($componentBlocks as $componentBlock) {
                    $placements[$componentBlock->placement][$componentBlock->id] = $componentBlock;
                }
                foreach ($placements as $placement => $blocks) {
                    Buffer::start();
                    foreach ($blocks as $block) {
                        ?>
                        <div class="pageeditor-block-options" data-component-block-id="<?= $block ?>">
                            <button class="framelix-button framelix-button-small block-settings"
                                    data-icon-left="settings"
                                    title="__pagemyself_component_settings__"></button>
                            <div class="pageeditor-block-options-title">#<?= $block ?>
                                : <?= Lang::get($blockList[$block->blockClass]['title']) ?></div>
                            <button class="framelix-button framelix-button-small sort-block-down framelix-button-customcolor"
                                    data-icon-left="south" style="--color-custom-bg:#2190af; --color-custom-text:white;"
                                    title="__pagemyself_component_sort_down__"></button>
                            <button class="framelix-button framelix-button-small sort-block-up framelix-button-customcolor"
                                    data-icon-left="north" style="--color-custom-bg:#216daf; --color-custom-text:white;"
                                    title="__pagemyself_component_sort_up__"></button>
                        </div>
                        <?php
                    }
                    $content = Buffer::get();
                    $tabs->addTab($placement, strtoupper($placement), $content);
                }
                $tabs->show();
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getPost('framelix-form-themeSettings')) {
            $page = Page::getById(Request::getPost('page'));
            $themeInstance = $page->getThemeInstance();
            $form = new Form();
            $themeInstance->addThemeSettingFields($form);
            $values = $form->getConvertedSubmittedValues();
            foreach ($values as $key => $value) {
                WebsiteSettings::set('theme_' . $themeInstance->themeId . "_" . $key, $value);
            }
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->redirect();
        }
        if (Request::getPost('framelix-form-blockSettings')) {
            $componentBlock = ComponentBlock::getById(Request::getPost('componentBlockId'));
            $componentInstance = $componentBlock->getComponentInstance();
            $themeInstance = $componentBlock->page->getThemeInstance();
            $form = new Form();
            $componentInstance->addSettingFields($form);
            $themeInstance->addComponentSettingFields($form, $componentInstance);
            $values = $form->getConvertedSubmittedValues();
            $settings = $componentBlock->settings;
            foreach ($values as $key => $value) {
                $settings[$key] = $value;
            }
            $componentBlock->settings = $settings;
            $componentBlock->store();
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
        // call defaults will initialize them when not yet added
        // useful because this is the first page the user will open after setup
        Page::getDefault();
        ?>
        <div data-color-scheme="dark" class="pageeditor-frame"
             data-edit-url="<?= JsCall::getCallUrl(__CLASS__, 'custom') ?>">
            <div class="pageeditor-frame-top">
                <div class="pageeditor-frame-top-addressbar">
                    <button class="framelix-button" data-icon-left="chevron_left"
                            title="__pagemyself_pageeditor_page_back__" data-frame-action="back"></button>
                    <button class="framelix-button" data-icon-left="autorenew"
                            title="__pagemyself_pageeditor_page_reload__" data-frame-action="reload"></button>
                    <button class="framelix-button" data-icon-left="home"
                            title="__pagemyself_pageeditor_page_home__" data-frame-action="loadurl"
                            data-url="<?= Url::getApplicationUrl() ?>"></button>
                    <?php
                    $field = new Select();
                    $field->name = 'jumpToPage';
                    $field->chooseOptionLabel = '__pagemyself_pageeditor_jump_to_page__';
                    $field->showResetButton = false;
                    $pages = Page::getByCondition(sort: "+sort");
                    foreach ($pages as $page) {
                        $field->addOption($page->getPublicUrl(), $page->title);
                    }
                    $field->show();
                    ?>
                    <div class="pageeditor-address"></div>
                    <button class="framelix-button " data-icon-left="smartphone"
                            title="__pagemyself_pageeditor_page_mobile__" data-frame-action="mobile"></button>
                </div>

                <div class="pageeditor-frame-options">
                    <div class="pageeditor-frame-option-group">
                        <div class="pageeditor-frame-option-group-small"><?= Lang::get(
                                '__pagemyself_theme_options__'
                            ) ?></div>
                        <div>
                            <button class="framelix-button " data-icon-left="settings"
                                    title="__pagemyself_pageeditor_theme_settings__"
                                    data-frame-action="themeSettings"></button>
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
                        </div>
                    </div>
                    <div class="pageeditor-frame-option-group">
                        <div class="pageeditor-frame-option-group-small"><?= Lang::get(
                                '__pagemyself_page_options__'
                            ) ?></div>
                        <div>
                            <button class="framelix-button framelix-button-small page-options"
                                    data-icon-left="settings"
                                    title="__pagemyself_page_options__" data-url="<?= \Framelix\Framelix\View::getUrl(
                                \Framelix\PageMyself\View\Backend\Page\Index::class
                            ) ?>"></button>
                            <?= Lang::get('__pagemyself_pagetitle__') ?>:
                            <span class="pageeditor-frame-top-title"></span>
                        </div>
                    </div>
                    <div class="pageeditor-frame-option-group">
                        <div class="pageeditor-frame-option-group-small"><?= Lang::get(
                                '__pagemyself_block_options__'
                            ) ?></div>
                        <div>
                            <button class="framelix-button framelix-button-small block-list"
                                    data-icon-left="settings"
                                    title="__pagemyself_component_block_settings__"></button>
                            <button class="framelix-button framelix-button-small add-new-block" data-icon-left="add"
                                    title="__pagemyself_component_add__"></button>
                        </div>
                    </div>
                </div>
            </div>
            <iframe src="<?= Request::getGet('url') ?? \Framelix\Framelix\View::getUrl(
                \Framelix\PageMyself\View\Index::class
            ) ?>" width="100%"
                    frameborder="0"></iframe>
        </div>
        <script>
          PageMyselfPageEditor.config = <?=JsonUtils::encode([
              'tinymceUrl' => Url::getUrlToFile(Editor::TINYMCE_PATH, antiCacheParameter: false),
              'tinymcePluginsUrl' => Compiler::getDistUrl(FRAMELIX_MODULE, 'js', 'tinymce-plugins')
          ])?>;
          PageMyself.config = <?=JsonUtils::encode([
              'componentApiRequestUrl' => JsCall::getCallUrl(
                  \Framelix\PageMyself\View\Index::class,
                  'componentApiRequest'
              )
          ])?>;
        </script>
        <?php
    }
}