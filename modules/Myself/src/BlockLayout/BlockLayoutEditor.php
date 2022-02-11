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
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;

use function array_diff_key;
use function call_user_func_array;
use function copy;
use function file_exists;
use function is_numeric;
use function reset;
use function strip_tags;
use function substr;
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
        $action = $jsCall->parameters['action'] ?? Request::getGet('action');
        $rowId = $jsCall->parameters['rowId'] ?? null;
        $columnId = $jsCall->parameters['columnId'] ?? null;
        switch ($action) {
            case 'icon-picker':
                $listFile = __DIR__ . "/../../../Framelix/public/fonts/material-icons-list.txt";
                $icons = file($listFile);
                echo '<p class="framelix-alert">' . Lang::get('__myself_editor_icons_info__') . '</p>';
                echo '<div><input type="search" class="framelix-form-field-input" placeholder="' . Lang::get(
                        '__myself_editor_icons_search__'
                    ) . '" data-continuous-search="1"></div>';
                foreach ($icons as $icon) {
                    ?>
                    <div tabindex="0" class="framelix-space-click" data-name="<?= $icon ?>" title="<?= $icon ?>"
                         data-insert-self="1"
                         style="font-size: 24px; padding:10px; display: inline-block; cursor: pointer">
                        <span class="material-icons"><?= trim($icon) ?></span>
                    </div>
                    <?php
                }

                break;
            case 'save-template':
                $blockLayout = BlockLayout::create(JsonUtils::decode(Request::getPost('blockLayout')));
                $pageBlocks = [];
                foreach ($blockLayout->rows as $row) {
                    if ($row->settings->backgroundVideo) {
                        $row->settings->backgroundVideo = 'demo-video';
                    }
                    if ($row->settings->backgroundImage) {
                        $row->settings->backgroundImage = 'demo-image';
                    }
                    foreach ($row->columns as $column) {
                        if ($column->settings->backgroundVideo) {
                            $column->settings->backgroundVideo = 'demo-video';
                        }
                        if ($column->settings->backgroundImage) {
                            $column->settings->backgroundImage = 'demo-image';
                        }
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
                        if (count($keyParts) === 3) {
                            $arrValue = $template->{$keyParts[1]} ?? [];
                            $arrValue[$keyParts[2]] = $value;
                            $template->{$keyParts[1]} = $arrValue;
                        } else {
                            $template->{$keyParts[1]} = $value;
                        }
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
                JsonUtils::writeToFile($templateFolder . "/" . $template->templateFilename . ".json", $template, true);
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
                <?php
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
                                call_user_func_array(
                                    [BlockBase::class, "prepareTemplateSettingsForImport"],
                                    [&$pageBlockData['pageBlockSettings']]
                                );
                                call_user_func_array(
                                    [$pageBlock->pageBlockClass, "prepareTemplateSettingsForImport"],
                                    [&$pageBlockData['pageBlockSettings']]
                                );
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
                $pageBlocks = ArrayUtils::merge(
                    $page->getThemeSettings()->getFixedPageBlocks(),
                    PageBlock::getByCondition('page = {0}', [$page])
                );
                $allPageBlocks = [];
                foreach ($pageBlocks as $pageBlock) {
                    $pageBlockLayout = $pageBlock->getLayoutBlock();
                    $title = Lang::get('__myself_blocklayout_class_not_exist__', [$pageBlock->pageBlockClass]);
                    $status = [];
                    if ($pageBlockLayout) {
                        if ($pageBlock->flagDraft) {
                            $status[] = 'draft';
                        }
                        $title = strip_tags(
                            HtmlUtils::unescape(Lang::get($pageBlockLayout->getBlockLayoutLabel()))
                        );
                    } else {
                        $status[] = 'classnotexist';
                    }
                    $title = trim($title);
                    $allPageBlocks[$pageBlock->id] = [
                        'status' => $status,
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
            case 'grow':
            case 'shrink':
                $blockLayout = $page->getBlockLayout();
                $column = $blockLayout->rows[$rowId]->columns[$columnId] ?? null;
                if ($column) {
                    $column->settings->grow += $action === 'grow' ? 1 : -1;
                    if ($column->settings->grow < 1) {
                        $column->settings->grow = 1;
                    }
                    $page->blockLayout = $blockLayout;
                    $page->store();
                }
                break;
            case 'rows-sort':
                $blockLayout = $page->getBlockLayout();
                $rowsNew = [];
                foreach ($jsCall->parameters['rowIds'] as $id) {
                    $rowsNew[] = $blockLayout->rows[$id];
                }
                $blockLayout->rows = $rowsNew;
                $page->blockLayout = $blockLayout;
                $page->store();
                break;
            case 'row-add':
                $blockLayout = $page->getBlockLayout();
                $row = BlockLayoutRow::create(null);
                $row->columns[] = BlockLayoutColumn::create(null);
                $blockLayout->rows[] = $row;
                $page->blockLayout = $blockLayout;
                $page->store();
                break;
            case 'column-swap':
                $blockLayout = $page->getBlockLayout();
                $columnA = $blockLayout->rows[$jsCall->parameters['rowIdA']]->columns[$jsCall->parameters['columnIdA']];
                $columnB = $blockLayout->rows[$jsCall->parameters['rowIdB']]->columns[$jsCall->parameters['columnIdB']];
                $blockIdA = $columnA->pageBlockId;
                $blockIdB = $columnB->pageBlockId;
                $columnA->pageBlockId = $blockIdB;
                $columnB->pageBlockId = $blockIdA;
                $page->blockLayout = $blockLayout;
                $page->store();
                break;
            case 'column-add':
                $blockLayout = $page->getBlockLayout();
                $blockLayout->rows[$rowId]->columns[] = BlockLayoutColumn::create(null);
                $page->blockLayout = $blockLayout;
                $page->store();
                break;
            case 'column-remove':
                $blockLayout = $page->getBlockLayout();
                if (isset($blockLayout->rows[$rowId]->columns[$columnId])) {
                    $column = $blockLayout->rows[$rowId]->columns[$columnId];
                    if ($column->pageBlockId) {
                        PageBlock::getById($column->pageBlockId)?->delete();
                        $column->pageBlockId = 0;
                    }
                    unset($blockLayout->rows[$rowId]->columns[$columnId]);
                }
                if (!$blockLayout->rows[$rowId]->columns) {
                    unset($blockLayout->rows[$rowId]);
                }
                $page->blockLayout = $blockLayout;
                $page->store();
                break;
            case 'save-settings':
                $blockLayout = $page->getBlockLayout();
                $pageBlock = PageBlock::getById(Request::getGet('pageBlockId') ?? null);
                $layoutBlock = $pageBlock?->getLayoutBlock();
                $column = null;
                $row = null;
                $columnSettings = null;
                $rowSettings = null;
                if (is_numeric(Request::getGet('rowId')) && is_numeric(Request::getGet('columnId'))) {
                    $row = $blockLayout->getRow(Request::getGet('rowId'));
                    $column = $blockLayout->getColumn(Request::getGet('rowId'), Request::getGet('columnId'));
                    $rowSettings = $row->settings;
                    $columnSettings = $column->settings;
                }
                $form = self::getFormSettings($layoutBlock, $row, $column);
                $values = $form->getConvertedSubmittedValues();
                foreach ($values as $fieldName => $value) {
                    $nameSplit = ArrayUtils::splitKeyString($fieldName);
                    if ($nameSplit[0] === 'pageBlockValues') {
                        $pageBlock->{$nameSplit[1]} = $value;
                    } elseif ($nameSplit[0] === 'rowSettings') {
                        $rowSettings->{$nameSplit[1]} = $value;
                    } elseif ($nameSplit[0] === 'columnSettings') {
                        $columnSettings->{$nameSplit[1]} = $value;
                    } elseif ($nameSplit[0] === 'pageBlockSettings') {
                        if (!$pageBlock->pageBlockSettings) {
                            $pageBlock->pageBlockSettings = [];
                        }
                        $settings = $pageBlock->pageBlockSettings;
                        ArrayUtils::setValue($settings, $nameSplit[1], $value);
                        $pageBlock->pageBlockSettings = $settings;
                    }
                }
                $page->blockLayout = $blockLayout;
                $page->store();
                $pageBlock?->store();
                Toast::success('__framelix_saved__');
                Response::showFormAsyncSubmitResponse();
            case 'settings':
                $blockLayout = $page->getBlockLayout();
                $row = null;
                $column = null;
                if (is_numeric($rowId) && is_numeric($columnId)) {
                    $row = $blockLayout->getRow($rowId);
                    $column = $blockLayout->getColumn($rowId, $columnId);
                }
                $pageBlock = PageBlock::getById($column->pageBlockId ?? $jsCall->parameters['pageBlockId']);
                $layoutBlock = $pageBlock?->getLayoutBlock();
                $form = self::getFormSettings(
                    $layoutBlock,
                    $row,
                    $column
                );
                $form->stickyFormButtons = true;
                $form->submitUrl = JsCall::getCallUrl(
                    __CLASS__,
                    'save-settings',
                    [
                        'pageId' => $page,
                        'pageBlockId' => $pageBlock,
                        'rowId' => $rowId,
                        'columnId' => $columnId
                    ]
                );
                $form->addSubmitButton('save', '__framelix_save__', 'save', ColorName::PRIMARY);
                $form->show();
                ?>
                <script>
                  (async function () {
                    const form = FramelixForm.getById('<?=$form->id?>')
                    await form.rendered
                    let color = ''
                    for (let fieldName in form.fields) {
                      const field = form.fields[fieldName]
                      if (field.name === 'title_columnsettings') {
                        color = '#81ccff'
                      }
                      if (field.name === 'title_rowsettings') {
                        color = '#ff81a5'
                      }
                      if (color.length) field.container.css('border-left', '5px solid ' + color).css('padding-left', '10px')
                    }
                  })()
                </script>
                <?php
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
                $blockClasses = BlockBase::getAllUserChoosableClasses();
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
            $field->addOption($template->templateFilename, $template->label['en']);
        }
        $field->addOption('new', '__myself_templateeditor_choose_new__');
        $field->defaultValue = $jsCall->parameters['template'] ?? 'new';
        $form->addField($field);

        $field = new Text();
        $field->name = 'templateData[label][en]';
        $field->label = '__myself_templateeditor_label__';
        $field->required = true;
        $field->defaultValue = $selectedTemplate->label['en'] ?? null;
        $form->addField($field);

        $field = new Textarea();
        $field->name = 'templateData[description][en]';
        $field->label = '__myself_templateeditor_desc__';
        $field->defaultValue = $selectedTemplate->description['en'] ?? null;
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
     * Get form for settings
     * @param BlockBase|null $blockBase
     * @param BlockLayoutRow|null $row
     * @param BlockLayoutColumn|null $column
     * @return Form
     */
    public static function getFormSettings(
        ?BlockBase $blockBase,
        ?BlockLayoutRow $row,
        ?BlockLayoutColumn $column
    ): Form {
        $form = new Form();
        $form->id = "settings";

        $rowSettings = $row->settings ?? null;
        $columnSettings = $column->settings ?? null;

        if ($columnSettings) {
            $field = new Html();
            $field->name = 'title_columnsettings';
            $field->defaultValue = '<div class="framelix-alert">' . Lang::get(
                    '__myself_blocklayout_settings_info_columnsettings_'
                ) . '</div>';
            $form->addField($field);
        }

        $prevFields = $form->fields;
        $blockBase?->addSettingsFields($form);
        $additionalFields = array_diff_key($form->fields, $prevFields);

        foreach ($additionalFields as $field) {
            unset($form->fields[$field->name]);
            if ($field->hasVisibilityCondition()) {
                $condition = $field->getVisibilityCondition();
                foreach ($condition->data as $key => $conditionRow) {
                    if (isset($conditionRow['field'])) {
                        $conditionRow['field'] = 'pageBlockSettings[' . $conditionRow['field'] . ']';
                        $condition->data[$key] = $conditionRow;
                    }
                }
            }
            $field->name = "pageBlockSettings[$field->name]";
            $form->fields[$field->name] = $field;
        }

        if ($blockBase && !$blockBase->pageBlock->fixedPlacement) {
            $field = new Toggle();
            $field->name = "pageBlockValues[flagDraft]";
            $field->label = '__myself_pageblock_edit_internal_draft__';
            $form->addField($field);
        }

        if ($columnSettings) {
            $field = new Number();
            $field->name = 'columnSettings[padding]';
            $field->label = '__myself_blocklayout_columnsetting_padding__';
            $field->min = 0;
            $field->max = 10000;
            $form->addField($field);

            $field = new Number();
            $field->name = 'columnSettings[minWidth]';
            $field->label = '__myself_pageblocks_minwidth__';
            $field->max = 10000;
            $form->addField($field);

            $field = new Number();
            $field->name = 'columnSettings[minHeight]';
            $field->label = '__myself_pageblocks_minheight__';
            $field->max = 10000;
            $form->addField($field);

            $field = new Select();
            $field->name = 'columnSettings[textVerticalAlignment]';
            $field->label = '__myself_pageblocks_textverticalalignment__';
            $field->addOption('top', '__myself_align_top__');
            $field->addOption('center', '__myself_align_center__');
            $field->addOption('bottom', '__myself_align_bottom__');
            $field->defaultValue = 'center';
            $field->getVisibilityCondition()->notEmpty('minHeight');
            $form->addField($field);

            $field = new Select();
            $field->name = 'columnSettings[textSize]';
            $field->label = '__myself_pageblocks_textsize__';
            $field->labelDescription = Lang::get('__myself_pageblocks_textsize_desc__')
                . "<br/>" . Lang::get('__myself_pageblocks_pageblock_setting_override__');
            for ($i = 50; $i <= 500; $i += 10) {
                $field->addOption($i, $i . "%");
            }
            $field->defaultValue = 100;
            $form->addField($field);

            $field = new Color();
            $field->name = 'columnSettings[textColor]';
            $field->label = '__myself_pageblocks_textcolor__';
            $field->labelDescription = Lang::get('__myself_pageblocks_textcolor_desc__') . "<br/>" . Lang::get(
                    '__myself_pageblocks_pageblock_setting_override__'
                );
            $form->addField($field);

            $field = new Select();
            $field->name = 'columnSettings[textAlignment]';
            $field->label = '__myself_blocklayout_columnsetting_textalignment__';
            $field->labelDescription = Lang::get(
                    '__myself_blocklayout_columnsetting_textalignment_desc__'
                ) . "<br/>" . Lang::get('__myself_pageblocks_pageblock_setting_override__');
            $field->addOption('left', '__myself_align_left__');
            $field->addOption('center', '__myself_align_center__');
            $field->addOption('right', '__myself_align_right__');
            $form->addField($field);

            $field = new Color();
            $field->name = 'columnSettings[backgroundColor]';
            $field->label = '__myself_pageblocks_backgroundcolor__';
            $form->addField($field);

            $field = new MediaBrowser();
            $field->name = 'columnSettings[backgroundImage]';
            $field->label = '__myself_pageblocks_backgroundimage__';
            $field->setOnlyImages();
            $form->addField($field);

            $field = new MediaBrowser();
            $field->name = 'columnSettings[backgroundVideo]';
            $field->label = '__myself_pageblocks_backgroundvideo__';
            $field->setOnlyVideos();
            $form->addField($field);

            $field = new Select();
            $field->name = 'columnSettings[backgroundSize]';
            $field->label = '__myself_pageblocks_backgroundsize__';
            $field->addOption('contain', '__myself_pageblocks_backgroundsize_contain__');
            $field->addOption('cover', '__myself_pageblocks_backgroundsize_cover__');
            $field->defaultValue = 'cover';
            $field->getVisibilityCondition()->notEmpty('columnSettings[backgroundImage]')->or()->notEmpty(
                'columnSettings[backgroundVideo]'
            );
            $form->addField($field);

            $field = new Select();
            $field->name = 'columnSettings[backgroundPosition]';
            $field->label = '__myself_pageblocks_backgroundposition__';
            $field->addOption('top', '__myself_align_top__');
            $field->addOption('center', '__myself_align_center__');
            $field->addOption('bottom', '__myself_align_bottom__');
            $field->getVisibilityCondition()->notEmpty('columnSettings[backgroundImage]')->or()->notEmpty(
                'columnSettings[backgroundVideo]'
            );
            $form->addField($field);

            $field = new Select();
            $field->name = 'columnSettings[fadeIn]';
            $field->label = '__myself_blocklayout_columnsetting_fadein__';
            $field->addOption('blur', '__myself_blocklayout_columnsetting_fadein_blur__');
            $field->addOption('fly', '__myself_blocklayout_columnsetting_fadein_fly__');
            $field->addOption('scale', '__myself_blocklayout_columnsetting_fadein_scale__');
            $form->addField($field);

            $field = new Toggle();
            $field->name = 'columnSettings[fadeOut]';
            $field->label = '__myself_blocklayout_columnsetting_fadeout__';
            $form->addField($field);
        }

        if ($rowSettings) {
            $field = new Html();
            $field->name = 'title_rowsettings';
            $field->defaultValue = '<div class="framelix-alert">' . Lang::get(
                    '__myself_blocklayout_settings_info_rowsettings_', [count($row->columns ?? [])]
                ) . '</div>';
            $form->addField($field);

            $field = new Number();
            $field->name = 'rowSettings[gap]';
            $field->label = '__myself_blocklayout_rowsetting_gap__';
            $field->labelDescription = '__myself_blocklayout_rowsetting_gap_desc__';
            $field->min = 0;
            $field->max = 10000;
            $form->addField($field);

            $field = new Number();
            $field->name = 'rowSettings[maxWidth]';
            $field->label = '__myself_blocklayout_rowsetting_maxwidth__';
            $field->labelDescription = '__myself_blocklayout_rowsetting_maxwidth_desc__';
            $field->min = 0;
            $field->max = 10000;
            $form->addField($field);

            $field = new Select();
            $field->name = 'rowSettings[alignment]';
            $field->label = '__myself_blocklayout_rowsetting_alignment__';
            $field->labelDescription = '__myself_align_desc__';
            $field->addOption('left', '__myself_align_left__');
            $field->addOption('center', '__myself_align_center__');
            $field->defaultValue = 'left';
            $field->getVisibilityCondition()->notEmpty('rowSettings[maxWidth]');
            $form->addField($field);

            $field = new MediaBrowser();
            $field->name = 'rowSettings[backgroundImage]';
            $field->label = '__myself_pageblocks_backgroundimage__';
            $field->setOnlyImages();
            $form->addField($field);

            $field = new MediaBrowser();
            $field->name = 'rowSettings[backgroundVideo]';
            $field->label = '__myself_pageblocks_backgroundvideo__';
            $field->labelDescription = '__myself_pageblocks_backgroundvideo_desc__';
            $field->setOnlyVideos();
            $form->addField($field);

            $field = new Select();
            $field->name = 'rowSettings[backgroundSize]';
            $field->label = '__myself_pageblocks_backgroundsize__';
            $field->addOption('contain', '__myself_pageblocks_backgroundsize_contain__');
            $field->addOption('cover', '__myself_pageblocks_backgroundsize_cover__');
            $field->defaultValue = 'cover';
            $field->getVisibilityCondition()->notEmpty('backgroundVideo')->or()->notEmpty('backgroundImage');
            $form->addField($field);

            $field = new Select();
            $field->name = 'rowSettings[backgroundPosition]';
            $field->label = '__myself_pageblocks_backgroundposition__';
            $field->addOption('top', '__myself_align_top__');
            $field->addOption('center', '__myself_align_center__');
            $field->addOption('bottom', '__myself_align_bottom__');
            $field->defaultValue = 'center';
            $field->getVisibilityCondition()
                ->notEmpty('rowSettings[backgroundVideo]')
                ->or()
                ->notEmpty('rowSettings[backgroundImage]');
            $form->addField($field);
        }

        foreach ($form->fields as $field) {
            $splitKey = ArrayUtils::splitKeyString($field->name);
            if (count($splitKey) <= 1) {
                continue;
            }
            if ($field->label === null) {
                $field->label = ClassUtils::getLangKey(
                    $blockBase,
                    $splitKey[1]
                );
            }
            if ($field->labelDescription === null) {
                $field->labelDescription = substr($field->label, 0, -2) . "_desc__";
                if (!Lang::keyExist($field->labelDescription)) {
                    $field->labelDescription = null;
                }
            }
            if ($splitKey[0] === 'columnSettings') {
                $field->defaultValue = ArrayUtils::getValue($columnSettings, $splitKey[1]) ?? $field->defaultValue;
            } elseif ($splitKey[0] === 'rowSettings') {
                $field->defaultValue = ArrayUtils::getValue($rowSettings, $splitKey[1]) ?? $field->defaultValue;
            } elseif ($splitKey[0] === 'pageBlockValues') {
                $field->defaultValue = ArrayUtils::getValue(
                        $blockBase->pageBlock,
                        $splitKey[1]
                    ) ?? $field->defaultValue;
            } elseif ($splitKey[0] === 'pageBlockSettings') {
                $field->defaultValue = ArrayUtils::getValue(
                        $blockBase->pageBlock->pageBlockSettings ?? null,
                        $splitKey[1]
                    ) ?? $field->defaultValue;
            }
        }

        return $form;
    }
}