<?php

namespace Framelix\Myself\BlockLayout;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\ColorName;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;

use function array_unshift;
use function call_user_func_array;
use function copy;
use function file_exists;
use function preg_replace;
use function reset;
use function str_replace;
use function strip_tags;
use function unlink;

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
        $page = Page::getById($jsCall->parameters['pageId'] ?? Request::getGet('pageId') ?? 0);
        switch ($jsCall->parameters['action'] ?? Request::getGet('action')) {
            case 'save-template':
                $blockLayout = BlockLayout::create(JsonUtils::decode(Request::getPost('blockLayout')));
                $pageBlocks = [];
                foreach ($blockLayout->rows as $row) {
                    foreach ($row->columns as $column) {
                        $pageBlocks[$column->pageBlockId] = $column->pageBlockId;
                    }
                }
                $pageBlocks = PageBlock::getByIds($pageBlocks);
                $pageBlockData = [];
                foreach ($pageBlocks as $pageBlock) {
                    $settings = $pageBlock->pageBlockSettings ?? [];
                    call_user_func_array(
                        [BlockBase::class, "prepareTemplateSettingsForExport"],
                        [&$settings]
                    );
                    call_user_func_array(
                        [$pageBlock->pageBlockClass, "prepareTemplateSettingsForExport"],
                        [&$settings]
                    );
                    $pageBlockData[$pageBlock->id] = [
                        'pageBlockClass' => $pageBlock->pageBlockClass,
                        'pageBlockSettings' => $settings
                    ];
                }
                $themeBlock = $page->getThemeBlock();
                $templates = $themeBlock->getTemplates();
                $templateFolder = $page->getThemeBlock()->getThemePublicFolderPath();
                $selectedTemplate = Request::getPost('template');
                if ($selectedTemplate === 'new' || !isset($templates[$selectedTemplate])) {
                    $count = 0;
                    while (true) {
                        $count++;
                        $selectedTemplate = "template-$count";
                        $selectedTemplateFile = $templateFolder . "/" . $selectedTemplate . ".json";
                        if (!file_exists($selectedTemplateFile)) {
                            break;
                        }
                    }
                    $template = new Template($themeBlock, $selectedTemplate);
                } else {
                    $template = $templates[$selectedTemplate];
                }
                $form = self::getFormTemplateEditor($jsCall, $page);
                $formValues = $form->getConvertedSubmittedValues();
                foreach ($formValues as $key => $value) {
                    $keyParts = ArrayUtils::splitKeyString($key);
                    if ($keyParts[0] === 'templateData') {
                        $template->{$keyParts[1]} = $value;
                    }
                }
                $files = UploadedFile::createFromSubmitData('thumbnailFile');
                if ($files) {
                    $file = reset($files);
                    $oldFile = $template->getThumbnailPath();
                    if ($oldFile) {
                        unlink($oldFile);
                    }
                    $thumbnailFile = $templateFolder . "/" . $selectedTemplate . "." . $file->getExtension();
                    copy($file->path, $thumbnailFile);
                    $template->thumbnailExtension = $file->getExtension();
                }
                if (!$template->thumbnailExtension) {
                    Response::showFormValidationErrorResponse('__myself_templateeditor_thumbnail_required__');
                }
                $template->blockLayout = $blockLayout;
                $template->pageBlockData = $pageBlockData;
                JsonUtils::writeToFile($templateFolder . "/" . $template->templateFilename . ".json", $template);
                $jsCall->result = true;
                Toast::success('__framelix_saved__');
                break;
            case 'template-editor':
                $form = self::getFormTemplateEditor($jsCall, $page);
                $form->addSubmitButton('save', '__framelix_save__', 'save');
                $form->show();
                $params = $jsCall->parameters;
                ?>
                <script>
                  (function () {
                    const form = FramelixForm.getById('<?=$form->id?>')
                    form.container.on(FramelixForm.EVENT_SUBMITTED, async function () {
                      if (await form.submitRequest.getJson() === true) {
                        location.reload()
                      }
                    })
                    form.fields['template'].container.on(FramelixFormField.EVENT_CHANGE_USER, function () {
                      let params = <?=JsonUtils::encode($params)?>;
                      params.template = form.fields['template'].getValue()
                      FramelixModal.callPhpMethod(<?=JsonUtils::encode(
                          Url::create()
                      )?>, params, { instance: FramelixModal.currentInstance })
                    })
                  })()
                </script>
                <?
                break;
            case 'insert-template':
                $templates = $page->getThemeBlock()->getTemplates();
                $template = $templates[$jsCall->parameters['id']] ?? null;
                if ($template) {
                    Storable::deleteMultiple($page->getPageBlocks());
                    $blockLayout = $template->blockLayout;
                    foreach ($blockLayout->rows as $row) {
                        foreach ($row->columns as $column) {
                            if ($column->pageBlockId) {
                                $pageBlockData = $template->pageBlockData[$column->pageBlockId];
                                $pageBlock = new PageBlock();
                                $pageBlock->page = $page;
                                $pageBlock->flagDraft = false;
                                $pageBlock->pageBlockClass = $pageBlockData['pageBlockClass'];
                                $pageBlock->pageBlockSettings = $pageBlockData['pageBlockSettings'];
                                $pageBlock->store();
                                $column->pageBlockId = $pageBlock->id;
                            }
                        }
                    }
                    $page->blockLayout = $blockLayout;
                    $page->store();
                    Toast::success('__myself_blocklayout_templates_inserted__');
                }
                break;
            case 'fetch-settings':
                $blockLayout = $page->getBlockLayout();
                $pageBlocks = PageBlock::getByCondition(
                    '(fixedPlacement IS NULL && page = {1}) || (fixedPlacement IS NOT NULL && themeClass = {0})',
                    [$page->getThemeClass(), $page]
                );
                $allPageBlocks = [];
                foreach ($pageBlocks as $pageBlock) {
                    $title = strip_tags(
                        HtmlUtils::unescape(Lang::get($pageBlock->getLayoutBlock()->getBlockLayoutLabel()))
                    );
                    $title = trim($title);
                    $allPageBlocks[$pageBlock->id] = [
                        'flagDraft' => $pageBlock->flagDraft,
                        'fixedPlacement' => $pageBlock->fixedPlacement,
                        'blockName' => ClassUtils::getLangKey($pageBlock->pageBlockClass),
                        'title' => $title
                    ];
                }
                $templates = $page->getThemeBlock()->getTemplates();
                $templatesEditorData = [];
                foreach ($templates as $key => $template) {
                    $templatesEditorData[$key] = [
                        'thumbnailUrl' => Url::getUrlToFile($template->getThumbnailPath())
                    ];
                }
                $jsCall->result = [
                    'blockLayout' => $blockLayout,
                    'templates' => $templates,
                    'templatesEditorData' => $templatesEditorData,
                    'allPageBlocks' => $allPageBlocks,
                    'devMode' => Config::isDevMode()
                ];
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
                $blockLayout = $page->getBlockLayout();
                $rowId = Request::getGet('rowId');
                $columnId = Request::getGet('columnId');
                $columnSettings = $blockLayout->getColumn($rowId, $columnId)->settings;
                $columnSettingsForm = self::getFormColumnSettings($columnSettings);
                foreach ($columnSettingsForm->fields as $field) {
                    $columnSettings->{$field->name} = $field->getConvertedSubmittedValue();
                }
                $page->blockLayout = $blockLayout;
                $page->store();
                Toast::success('__framelix_saved__');
                Response::showFormAsyncSubmitResponse();
            case 'save-layout':
                $oldPost = $_POST;
                $blockLayout = new BlockLayout();
                // convert values with form value converter
                if (isset($jsCall->parameters['rows'])) {
                    foreach ($jsCall->parameters['rows'] as $rowData) {
                        $_POST = $rowData['settings'];
                        $row = BlockLayoutRow::create($rowData);
                        $row->columns = [];
                        $blockLayout->rows[] = $row;
                        $form = self::getFormRowSettings(BlockLayoutRowSettings::create($rowData['settings']));
                        foreach ($form->fields as $field) {
                            $row->settings->{$field->name} = $field->getConvertedSubmittedValue();
                        }
                        if (isset($rowData['columns'])) {
                            foreach ($rowData['columns'] as $columnData) {
                                $_POST = $columnData['settings'];
                                $column = BlockLayoutColumn::create($columnData);
                                $row->columns[] = $column;
                                $form = self::getFormColumnSettings(
                                    BlockLayoutColumnSettings::create($columnData['settings'])
                                );
                                foreach ($form->fields as $field) {
                                    $column->settings->{$field->name} = $field->getConvertedSubmittedValue();
                                }
                            }
                        }
                    }
                }
                $_POST = $oldPost;
                $page->blockLayout = $blockLayout;
                $page->store();
                Toast::success('__framelix_saved__');
                break;
            case 'column-settings':
                $pageBlock = PageBlock::getById($jsCall->parameters['pageBlockId'] ?? null);
                $columnSettingsForm = self::getFormColumnSettings(
                    BlockLayoutColumnSettings::create($jsCall->parameters['settings'])
                );
                $columnSettingsForm->stickyFormButtons = true;
                $columnSettingsForm->submitUrl = JsCall::getCallUrl(
                    __CLASS__,
                    'save-column-settings',
                    [
                        'pageId' => $page,
                        'rowId' => $jsCall->parameters['rowId'] ?? null,
                        'columnId' => $jsCall->parameters['columnId'] ?? null
                    ]
                );

                $columnSettingsForm->addSubmitButton('saveClose', '__myself_blocklayout_save_and_close__', 'save_alt');
                $columnSettingsForm->addSubmitButton('save', '__framelix_save__', 'save', ColorName::PRIMARY);
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
                        $form->stickyFormButtons = true;
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
                            $form->addSubmitButton('saveClose', '__myself_blocklayout_save_and_close__', 'save_alt');
                            $form->addSubmitButton('save', '__framelix_save__', 'save', ColorName::PRIMARY);
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
                $form = self::getFormRowSettings(BlockLayoutRowSettings::create($jsCall->parameters['settings']));
                $form->stickyFormButtons = true;
                $form->addButton('save', '__framelix_ok__', 'save', ColorName::SUCCESS);
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

                $blockLayout = $page->getBlockLayout();
                $column = $blockLayout->getColumn($rowId, $columnId);
                $column->pageBlockId = $pageBlock->id;
                $page->blockLayout = $blockLayout;
                $page->store();
                break;
        }
    }

    /**
     * Get form for template editing
     * @param JsCall $jsCall
     * @param Page $page
     * @return Form
     */
    public static function getFormTemplateEditor(JsCall $jsCall, Page $page): Form
    {
        $themeBlock = $page->getThemeBlock();
        $templates = $themeBlock->getTemplates();
        $selectedTemplate = $templates[$jsCall->parameters['template'] ?? 'new']
            ?? new Template($themeBlock);

        $form = new Form();
        $form->id = "template-editor";
        $form->submitUrl = JsCall::getCallUrl(__CLASS__, 'save-template', ['pageId' => $page]);

        $field = new Hidden();
        $field->name = 'blockLayout';
        $field->defaultValue = $jsCall->parameters['blockLayout'] ?? null;
        $form->addField($field);

        $field = new Select();
        $field->name = 'template';
        $field->label = '__myself_templateeditor_choose__';
        foreach ($templates as $template) {
            $field->addOption($template->templateFilename, $template->label);
        }
        $field->addOption('new', '__myself_templateeditor_choose_new__');
        $field->defaultValue = $jsCall->parameters['template'] ?? 'new';
        $form->addField($field);

        $field = new Text();
        $field->name = 'templateData[label]';
        $field->label = '__myself_templateeditor_label__';
        $field->required = true;
        $field->defaultValue = $selectedTemplate->label;
        $form->addField($field);

        $field = new Textarea();
        $field->name = 'templateData[description]';
        $field->label = '__myself_templateeditor_desc__';
        $field->defaultValue = $selectedTemplate->description;
        $field->required = true;
        $form->addField($field);

        $field = new File();
        $field->name = 'thumbnailFile';
        $field->label = '__myself_templateeditor_thumbnail__';
        $field->setOnlyImages();
        $form->addField($field);

        $thumbnailFile = $selectedTemplate->getThumbnailPath();
        if ($thumbnailFile) {
            $field = new Html();
            $field->name = 'thumbnailImage';
            $field->defaultValue = '<img src="' . Url::getUrlToFile($thumbnailFile) . '" alt="" width="200">';
            $form->addField($field);
        }

        return $form;
    }

    /**
     * Get form for row settings
     * @param BlockLayoutRowSettings $settings
     * @return Form
     */
    public static function getFormRowSettings(BlockLayoutRowSettings $settings): Form
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
     * @param BlockLayoutColumnSettings $settings
     * @return Form
     */
    public static function getFormColumnSettings(BlockLayoutColumnSettings $settings): Form
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
        $field->name = 'textVerticalAlignment';
        $field->label = '__myself_pageblocks_textverticalalignment__';
        $field->labelDescription = '__myself_pageblocks_textverticalalignment_desc__';
        $field->addOption('top', '__myself_align_top__');
        $field->addOption('center', '__myself_align_center__');
        $field->addOption('bottom', '__myself_align_bottom__');
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name) ?? 'center';
        $field->getVisibilityCondition()->notEmpty('minHeight');
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

        $field = new Select();
        $field->name = 'fadeIn';
        $field->label = '__myself_blocklayout_columnsetting_fadein__';
        $field->labelDescription = '__myself_blocklayout_columnsetting_fade_desc__';
        $field->addOption('blur', '__myself_blocklayout_columnsetting_fade_blur__');
        $field->addOption('fly', '__myself_blocklayout_columnsetting_fade_fly__');
        $field->addOption('scale', '__myself_blocklayout_columnsetting_fade_scale__');
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'fadeOut';
        $field->label = '__myself_blocklayout_columnsetting_fadeout__';
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        return $form;
    }
}