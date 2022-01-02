<?php

namespace Framelix\Myself;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;

use function array_unshift;
use function is_array;
use function preg_replace;
use function str_replace;

/**
 * BlockLayoutEditor
 */
class BlockLayoutEditor
{
    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        $page = Page::getById(Request::getGet('pageId'));
        switch ($jsCall->parameters['action'] ?? Request::getGet('action')) {
            case 'fetch-settings':
                $config = $page->blockLayout;
                $pageBlocks = PageBlock::getByCondition(
                    '(fixedPlacement IS NULL && page = {1}) || (fixedPlacement IS NOT NULL && theme = {0})',
                    [$page->getTheme(), $page]
                );
                $config['allPageBlocks'] = [];
                foreach ($pageBlocks as $pageBlock) {
                    $config['allPageBlocks'][$pageBlock->id] = [
                        'fixedPlacement' => $pageBlock->fixedPlacement,
                        'title' => ClassUtils::getLangKey($pageBlock->pageBlockClass)
                    ];
                }
                if (is_array($config['rows'] ?? null)) {
                    foreach ($config['rows'] as $rowId => $row) {
                        $columns = $row['columns'] ?? null;
                        if (is_array($columns)) {
                            foreach ($columns as $columnId => $columnRow) {
                                $config['rows'][$rowId]['columns'][$columnId]['pageBlockId'] = $pageBlocks[$columnRow['pageBlockId'] ?? 0] ?? null;
                            }
                        }
                    }
                }
                if (!($config['rows'] ?? null)) {
                    $config['rows'] = [];
                }
                $jsCall->result = $config;
                break;
            case 'save-pageblock-settings':
                $pageBlock = PageBlock::getById(Request::getGet('pageBlockId') ?? null);
                $block = $pageBlock->getLayoutBlock();
                $forms = $block->getSettingsForms();
                $form = $forms[Request::getGet('formKey')];
                $block->setValuesFromSettingsForm($form);
                $pageBlock->store();
                Toast::success('__framelix_saved__');
                Response::showFormAsyncSubmitResponse();
            case 'save-column-settings':
                $rows = JsonUtils::decode(Request::getPost('__rows'));
                $rowId = Request::getGet('rowId');
                $columnId = Request::getGet('columnId');
                $columnSettingsForm = self::getFormColumnSettings(null);
                ArrayUtils::setValue(
                    $rows,
                    $rowId . '[columns][' . $columnId . '][settings]',
                    $columnSettingsForm->getConvertedSubmittedValues()
                );
                $page->blockLayout = ['rows' => $rows];
                $page->store();
                Toast::success('__framelix_saved__');
                Response::showFormAsyncSubmitResponse();
            case 'save-layout':
                $page->blockLayout = ['rows' => $jsCall->parameters['rows']];
                $page->store();
                Toast::success('__framelix_saved__');
                break;
            case 'column-settings':
                $pageBlock = PageBlock::getById($jsCall->parameters['pageBlockId'] ?? null);
                $columnSettingsForm = self::getFormColumnSettings($jsCall->parameters['settings']);
                $columnSettingsForm->submitUrl = JsCall::getCallUrl(
                    __CLASS__,
                    'save-column-settings',
                    [
                        'pageId' => $page,
                        'rowId' => $jsCall->parameters['rowId'] ?? null,
                        'columnId' => $jsCall->parameters['columnId'] ?? null
                    ]
                );
                $field = new Hidden();
                $field->name = '__rows';
                $field->defaultValue = JsonUtils::encode($jsCall->parameters['rows']);
                $columnSettingsForm->addField($field);

                $columnSettingsForm->addSubmitButton('save', '__framelix_save__', 'save');
                if ($pageBlock) {
                    $block = $pageBlock->getLayoutBlock();
                    $forms = $block->getSettingsForms();
                    $tabs = new Tabs();
                    $tabs->id = "pageblock-" . $pageBlock->id;
                    if (!$pageBlock->fixedPlacement) {
                        Buffer::start();
                        $columnSettingsForm->show();
                        $content = Buffer::get();
                        $tabs->addTab("columnsettings", '__myself_blocklayout_settings_column__', $content);
                    }
                    foreach ($forms as $key => $form) {
                        $form->id = $form->id ?? $key;
                        $form->submitUrl = JsCall::getCallUrl(
                            __CLASS__,
                            'save-pageblock-settings',
                            ['pageBlockId' => $pageBlock, "formKey" => $key]
                        );
                        foreach ($form->fields as $field) {
                            $keyParts = ArrayUtils::splitKeyString($field->name);
                            if ($field->label === null) {
                                $field->label = ClassUtils::getLangKey(
                                    $pageBlock->pageBlockClass,
                                    $field->name
                                );
                                $field->label = preg_replace("~\[(.*?)\]~", "_$1", $field->label);
                                $field->label = str_replace("_pageblocksettings", "", $field->label);
                            }
                            if ($field->labelDescription === null) {
                                $langKey = ClassUtils::getLangKey(
                                    $pageBlock->pageBlockClass,
                                    $field->name . "_desc"
                                );
                                $langKey = preg_replace("~\[(.*?)\]~", "_$1", $langKey);
                                $langKey = str_replace("_pageblocksettings", "", $langKey);
                                if (Lang::keyExist($langKey)) {
                                    $field->labelDescription = Lang::get($langKey);
                                }
                            }
                            $field->defaultValue = ArrayUtils::getValue(
                                    $pageBlock,
                                    $keyParts
                                ) ?? $field->defaultValue;
                            array_unshift($keyParts, "pageBlockSettings");
                        }
                        $label = $form->label ?? ClassUtils::getLangKey(
                                $pageBlock->pageBlockClass,
                                "form_" . $form->id
                            );
                        $form->label = $label;
                        Buffer::start();
                        foreach ($form->fields as $field) {
                            if ($field instanceof Hidden || $field instanceof Html) {
                                continue;
                            }
                            $form->addSubmitButton('save', '__framelix_save__', 'save');
                            break;
                        }
                        $block->showSettingsForm($form);
                        $content = Buffer::get();
                        $tabs->addTab($form->id, $label, $content);
                    }
                    $tabs->show();
                } else {
                    $columnSettingsForm->show();
                }
                break;
            case 'row-settings':
                $form = self::getFormRowSettings($jsCall->parameters['settings']);
                $form->addButton('save', '__framelix_ok__', 'save', 'success');
                $form->show();
                break;
            case 'select-new-page-block':
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
                    $html .= '<div class="myself-page-block-create-entry framelix-space-click" tabindex="0" data-page-block-class="' . $blockClass . '" ' . $style . '>
                        <div class="myself-page-block-create-select-title">' . $title . '</div>
                        ' . $desc . '                        
                    </div>';
                }
                $html .= '</div>';
                $field->defaultValue = $html;

                $form->show();
                break;
            case 'create-page-block':
                $rowId = $jsCall->parameters['rowId'];
                $columnId = $jsCall->parameters['columnId'];
                $pageBlock = new PageBlock();
                $pageBlock->page = $page;
                $pageBlock->flagDraft = false;
                $pageBlock->pageBlockClass = $jsCall->parameters['pageBlockClass'];
                $pageBlock->store();
                $blockLayout = $page->blockLayout;
                ArrayUtils::setValue(
                    $blockLayout,
                    'rows[' . $rowId . '][columns][' . $columnId . '][pageBlockId]',
                    $pageBlock->id
                );
                $page->blockLayout = $blockLayout;
                $page->store();
                break;
        }
    }

    /**
     * Get form for row settings
     * @param array|null $settings
     * @return Form
     */
    public static function getFormRowSettings(?array $settings): Form
    {
        $form = new Form();
        $form->id = "rowsettings";

        $field = new Number();
        $field->name = 'gap';
        $field->label = '__myself_blocklayout_rowsetting_gap__';
        $field->labelDescription = '__myself_blocklayout_rowsetting_gap_desc__';
        $field->min = 0;
        $field->max = 10000;
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Number();
        $field->name = 'maxWidth';
        $field->label = '__myself_blocklayout_rowsetting_maxwidth__';
        $field->labelDescription = '__myself_blocklayout_rowsetting_maxwidth_desc__';
        $field->min = 0;
        $field->max = 10000;
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Select();
        $field->name = 'alignment';
        $field->label = '__myself_blocklayout_rowsetting_alignment__';
        $field->labelDescription = '__myself_align_desc__';
        $field->addOption('left', '__myself_align_left__');
        $field->addOption('center', '__myself_align_center__');
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $field->getVisibilityCondition()->notEmpty('maxWidth');
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundImage';
        $field->label = '__myself_pageblocks_backgroundimage__';
        $field->setOnlyImages();
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundVideo';
        $field->label = '__myself_pageblocks_backgroundvideo__';
        $field->labelDescription = '__myself_pageblocks_backgroundvideo_desc__';
        $field->setOnlyVideos();
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Select();
        $field->name = 'backgroundSize';
        $field->label = '__myself_pageblocks_backgroundsize__';
        $field->labelDescription = '__myself_pageblocks_backgroundsize_desc__';
        $field->addOption('contain', '__myself_pageblocks_backgroundsize_contain__');
        $field->addOption('cover', '__myself_pageblocks_backgroundsize_cover__');
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name) ?? 'cover';
        $field->getVisibilityCondition()->notEmpty('backgroundVideo')->or()->notEmpty('backgroundImage');
        $form->addField($field);

        return $form;
    }


    /**
     * Get form for column settings
     * @param array|null $settings
     * @return Form
     */
    public static function getFormColumnSettings(?array $settings): Form
    {
        $form = new Form();
        $form->id = "columnsettings";

        $field = new Number();
        $field->name = 'padding';
        $field->label = '__myself_blocklayout_columnsetting_padding__';
        $field->labelDescription = '__myself_blocklayout_columnsetting_padding_desc__';
        $field->min = 0;
        $field->max = 10000;
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Number();
        $field->name = 'minWidth';
        $field->label = '__myself_pageblocks_minwidth__';
        $field->labelDescription = '__myself_pageblocks_minwidth_desc__';
        $field->max = 10000;
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Number();
        $field->name = 'minHeight';
        $field->label = '__myself_pageblocks_minheight__';
        $field->labelDescription = '__myself_pageblocks_minheight_desc__';
        $field->max = 10000;
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Select();
        $field->name = 'textSize';
        $field->label = '__myself_pageblocks_textsize__';
        $field->labelDescription = Lang::get('__myself_pageblocks_textsize_desc__') . "<br/>" . Lang::get(
                '__myself_pageblocks_pageblock_setting_override__'
            );
        for ($i = 50; $i <= 500; $i += 10) {
            $field->addOption($i, $i . "%");
        }
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name) ?? 100;
        $form->addField($field);

        $field = new Color();
        $field->name = 'textColor';
        $field->label = '__myself_pageblocks_textcolor__';
        $field->labelDescription = Lang::get('__myself_pageblocks_textcolor_desc__') . "<br/>" . Lang::get(
                '__myself_pageblocks_pageblock_setting_override__'
            );
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Select();
        $field->name = 'textAlignment';
        $field->label = '__myself_blocklayout_columnsetting_textalignment__';
        $field->labelDescription = Lang::get(
                '__myself_blocklayout_columnsetting_textalignment_desc__'
            ) . "<br/>" . Lang::get('__myself_pageblocks_pageblock_setting_override__');
        $field->addOption('left', '__myself_align_left__');
        $field->addOption('center', '__myself_align_center__');
        $field->addOption('right', '__myself_align_right__');
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Color();
        $field->name = 'backgroundColor';
        $field->label = '__myself_pageblocks_backgroundcolor__';
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundImage';
        $field->label = '__myself_pageblocks_backgroundimage__';
        $field->setOnlyImages();
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundVideo';
        $field->label = '__myself_pageblocks_backgroundvideo__';
        $field->labelDescription = '__myself_pageblocks_backgroundvideo_desc__';
        $field->setOnlyVideos();
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Select();
        $field->name = 'backgroundSize';
        $field->label = '__myself_pageblocks_backgroundsize__';
        $field->labelDescription = '__myself_pageblocks_backgroundsize_desc__';
        $field->addOption('contain', '__myself_pageblocks_backgroundsize_contain__');
        $field->addOption('cover', '__myself_pageblocks_backgroundsize_cover__');
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name) ?? 'cover';
        $field->getVisibilityCondition()->notEmpty('backgroundVideo')->or()->notEmpty('backgroundImage');
        $form->addField($field);

        return $form;
    }
}