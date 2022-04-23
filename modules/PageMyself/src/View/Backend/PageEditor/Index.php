<?php

namespace Framelix\PageMyself\View\Backend\PageEditor;

use Framelix\Framelix\Form\Field\Editor;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\ColorName;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
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
                $instance = $page->getThemeInstance();
                echo '<h2>' . $instance->themeId . ': ' . Lang::get(
                        '__pagemyself_pageeditor_theme_settings__'
                    ) . '</h2>';
                echo '<p class="framelix-alert">' . Lang::get('__pagemyself_pageeditor_theme_settings_desc__') . '</p>';
                $form = new Form();
                $form->id = "themeSettings";
                $form->submitUrl = Url::getBrowserUrl();

                $fieldsEditable = 0;
                $instance->addSettingFields($form);
                foreach ($form->fields as $field) {
                    if (!($field instanceof Hidden) && !($field instanceof Html)) {
                        $fieldsEditable++;
                    }
                    $field->defaultValue = WebsiteSettings::get(
                            'theme_' . $instance->themeId . "_" . $field->name
                        ) ?? $field->defaultValue;
                    $field->label = Lang::get($field->label ?? '');
                    $field->labelDescription = Lang::get($field->labelDescription ?? '');
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
                    $settings = $componentBlock->settings;
                    $list = ComponentBase::getAvailableList();
                    $listRow = $list[$componentBlock->blockClass];

                    $instance = $componentBlock->getComponentInstance();
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
                        $field->defaultValue = '<div class="framelix-alert">' . Lang::get($listRow['help']) . '</div>';
                        $form->addField($field);
                    }

                    $instance->addSettingFields($form);
                    $fieldsEditable = 0;
                    foreach ($form->fields as $field) {
                        if (!($field instanceof Hidden) && !($field instanceof Html)) {
                            $fieldsEditable++;
                        }
                        $field->defaultValue = $settings[$field->name] ?? $field->defaultValue;
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
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getPost('framelix-form-themeSettings')) {
            $page = Page::getById(Request::getPost('page'));
            $instance = $page->getThemeInstance();
            $form = new Form();
            $instance->addSettingFields($form);
            $values = $form->getConvertedSubmittedValues();
            foreach ($values as $key => $value) {
                WebsiteSettings::set('theme_' . $instance->themeId . "_" . $key, $value);
            }
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->redirect();
        }
        if (Request::getPost('framelix-form-blockSettings')) {
            $componentBlock = ComponentBlock::getById(Request::getPost('componentBlockId'));
            $instance = $componentBlock->getComponentInstance();
            $form = new Form();
            $instance->addSettingFields($form);
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

                <button class="framelix-button " data-icon-left="settings"
                        title="__pagemyself_pageeditor_theme_settings__" data-frame-action="themeSettings"></button>
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