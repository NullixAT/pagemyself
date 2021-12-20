<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\Ace;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\Storable\PageBlock;

use function htmlentities;
use function is_array;

/**
 * WebsiteSettings
 */
class WebsiteSettings extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,content";

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'editor':
                $config = \Framelix\Myself\Storable\WebsiteSettings::get('blockLayout');
                $pageBlocks = PageBlock::getByCondition('fixedPlacement IS NULL');
                $config['allPageBlocks'] = [];
                foreach ($pageBlocks as $pageBlock) {
                    $config['allPageBlocks'][$pageBlock->id] = [
                        'title' => ClassUtils::getLangKey(
                            $pageBlock->pageBlockClass
                        )
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
                ?>
                <div class="myself-block-layout-editor"></div>
                <script>
                  (function () {
                    MyselfEdit.blockLayoutConfig = <?=JsonUtils::encode($config)?>;
                    MyselfEdit.renderBlockLayoutEditor()
                  })()
                </script>
                <?php
                break;
            case 'save-settings':
                \Framelix\Myself\Storable\WebsiteSettings::set('blockLayout[rows]', $jsCall->parameters['rows']);
                break;
            case 'column-settings':
                $form = self::getFormColumnSettings($jsCall->parameters['settings']);
                $form->addButton('save', '__ok__', 'save', 'success');
                $form->show();
                break;
            case 'row-settings':
                $form = self::getFormRowSettings($jsCall->parameters['settings']);
                $form->addButton('save', '__ok__', 'save', 'success');
                $form->show();
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
        $field->label = '__myself_align__';
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
        $field->name = 'minHeight';
        $field->label = '__myself_pageblocks_minheight__';
        $field->labelDescription = '__myself_pageblocks_minheight_desc__';
        $field->max = 10000;
        $field->defaultValue = ArrayUtils::getValue($settings, $field->name);
        $form->addField($field);

        $field = new Color();
        $field->name = 'textColor';
        $field->label = '__myself_pageblocks_textcolor__';
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

        return $form;
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Form::isFormSubmitted('globalsettings')) {
            $form = $this->getForm();
            $instance = \Framelix\Myself\Storable\WebsiteSettings::getInstance();
            $form->setStorableValues($instance);
            $instance->store();
            Toast::success('__saved__');
            Response::showFormAsyncSubmitResponse();
        }
        $form = $this->getForm();
        $form->addSubmitButton('save', '__save__', 'save');
        $form->show();
    }

    /**
     * Get form
     * @return Form
     */
    private function getForm(): Form
    {
        $instance = \Framelix\Myself\Storable\WebsiteSettings::getInstance();
        $form = new Form();
        $form->id = "globalsettings";
        $form->submitUrl = Url::create();

        $field = new MediaBrowser();
        $field->name = 'settings[og_image]';
        $field->label = '__myself_websitesettings_og_image__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $field->setOnlyImages();
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'settings[favicon]';
        $field->label = '__myself_websitesettings_favicon__';
        $field->labelDescription = '__myself_websitesettings_favicon_desc__';
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[author]';
        $field->label = '__myself_websitesettings_author__';
        $field->labelDescription = Lang::get('__myself_websitesettings_search_engine_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[keywords]';
        $field->label = '__myself_websitesettings_keywords__';
        $field->labelDescription = Lang::get('__myself_websitesettings_keywords_desc__') . "<br/>" . Lang::get(
                '__myself_websitesettings_search_engine_data__'
            );
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[og_site_name]';
        $field->label = '__myself_websitesettings_og_site_name__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[og_title]';
        $field->label = '__myself_websitesettings_og_title__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[og_description]';
        $field->label = '__myself_websitesettings_og_description__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Ace();
        $field->label = htmlentities(Lang::get('__myself_websitesettings_headhtml__'));
        $field->labelDescription = '__myself_websitesettings_headhtml_desc__';
        $field->name = 'settings[headHtml]';
        $field->mode = 'html';
        $field->initialHidden = true;
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Ace();
        $field->label = '__myself_websitesettings_pagejs__';
        $field->labelDescription = '__myself_websitesettings_pagejs_desc__';
        $field->name = 'settings[pagejs]';
        $field->mode = 'javascript';
        $field->initialHidden = true;
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Ace();
        $field->label = '__myself_websitesettings_pagecss__';
        $field->labelDescription = '__myself_websitesettings_pagecss_desc__';
        $field->name = 'settings[pagecss]';
        $field->mode = 'css';
        $field->initialHidden = true;
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        return $form;
    }

}