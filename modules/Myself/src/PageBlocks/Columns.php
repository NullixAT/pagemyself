<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\PageBlock;

/**
 * Default container block
 */
class Columns extends BlockBase
{
    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        $pageBlock = PageBlock::getById(Request::getGet('pageBlockId'));
        if (!$pageBlock) {
            return;
        }
        switch ($jsCall->action) {
            case 'save':
                $pageBlock->setPropertyKeyValue('pageBlockSettings', 'content', $jsCall->parameters['content'] ?? null);
                $pageBlock->store();
                $jsCall->result = true;
                break;
        }
    }

    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $baseCssClass = ClassUtils::getHtmlClass($this);
        $settings = $this->pageBlock->pageBlockSettings;
        $gap = (int)($settings['gap'] ?? 0);
        $maxWidth = (int)($settings['maxWidth'] ?? 0);
        $align = ($settings['align'] ?? 'center');
        $attributesOuter = new HtmlAttributes();
        $attributesOuter->addClass($baseCssClass . '-outer');
        $attributesInner = new HtmlAttributes();
        $attributesInner->addClass($baseCssClass . '-inner');
        if ($align === 'center') {
            $attributesOuter->setStyle('justify-content', $align);
        } elseif ($align === 'right') {
            $attributesOuter->setStyle('justify-content', 'flex-end');
        }
        if ($maxWidth > 0) {
            $attributesInner->setStyle('max-width', $maxWidth . "px");
        }
        if ($gap > 0) {
            $attributesInner->setStyle('gap', $gap . "px");
        }
        echo '<div ' . $attributesOuter . '>';
        echo '<div ' . $attributesInner . '>';
        for ($i = 0; $i <= 5; $i++) {
            $columnSettings = $settings['column'][$i] ?? null;
            if (!($columnSettings['enabled'] ?? null)) {
                continue;
            }
            $grow = (int)($columnSettings['width'] ?? 0);
            $padding = (int)($columnSettings['padding'] ?? 0);
            $minHeight = (int)($columnSettings['minHeight'] ?? 0);
            $textColor = ($columnSettings['textColor'] ?? null);
            $backgroundColor = ($columnSettings['backgroundColor'] ?? '');
            $backgroundImage = MediaFile::getById($columnSettings['backgroundImage'] ?? null);
            $backgroundVideo = MediaFile::getById($columnSettings['backgroundVideo'] ?? null);
            $attributes = new HtmlAttributes();
            if ($grow > 10) {
                $attributes->setStyle('flex-basis', $grow . "px");
            } else {
                $attributes->setStyle('flex-grow', $grow);
            }
            if ($padding > 0) {
                $attributes->setStyle('padding', $padding);
            }
            if ($minHeight > 0) {
                $attributes->setStyle('min-height', $minHeight . "px");
            }
            if ($backgroundColor) {
                $attributes->setStyle(
                    'background-color',
                    $backgroundColor
                );
            }
            $attributes->addClass($baseCssClass . '-column');
            echo '<div ' . $attributes . '>';
            $backgroundAlign = ($columnSettings['backgroundAlign'] ?? '');
            if ($backgroundAlign === 'top') {
                $backgroundAlign = 'flex-start';
            } elseif ($backgroundAlign === 'bottom') {
                $backgroundAlign = 'flex-end';
            } else {
                $backgroundAlign = 'center';
            }
            if ($backgroundVideo && $backgroundVideo->getPath()) {
                $poster = $backgroundImage?->getPath() ? 'data-poster="' . Url::getUrlToFile(
                        $backgroundImage->getBiggestThumbPath(MediaFile::THUMBNAIL_SIZE_LARGE)
                    ) . '"' : '';
                ?>
                <div class="<?= $baseCssClass ?>-background-media"
                     style="align-items: <?= $backgroundAlign ?>">
                    <div class="myself-lazy-load"
                         data-video="<?= Url::getUrlToFile($backgroundVideo->getPath()) ?>" <?= $poster ?>></div>
                </div>
                <?php
            } elseif ($backgroundImage && $backgroundImage->getPath()) {
                ?>
                <div class="<?= $baseCssClass ?>-background-media myself-lazy-load-parent-anchor"
                     style="align-items: <?= $backgroundAlign ?>">
                    <?
                    echo $backgroundImage->getLazyLoadContainer();
                    ?>
                </div>
                <?php
            }
            $contentAttributes = new HtmlAttributes();
            $contentAttributes->addClass($baseCssClass . "-content");
            if ($textColor) {
                $contentAttributes->setStyle('color', $textColor);
            }
            echo '<div ' . $contentAttributes . '>';
            LayoutUtils::showLiveEditableText(true, true, $this->pageBlock, "pageBlockSettings", "column[$i][content]");
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Get array of settings forms
     * If more then one form is returned, it will create tabs with forms
     * @return Form[]
     */
    public function getSettingsForms(): array
    {
        $forms = parent::getSettingsForms();

        $form = new Form();
        $form->id = "general";

        $field = new Number();
        $field->name = 'pageBlockSettings[gap]';
        $field->label = '__myself_pageblocks_columns_form_column_gap__';
        $field->labelDescription = '__myself_pageblocks_columns_form_column_gap_desc__';
        $field->min = 0;
        $field->max = 10000;
        $form->addField($field);

        $field = new Number();
        $field->name = 'pageBlockSettings[maxWidth]';
        $field->label = '__myself_pageblocks_columns_form_column_maxwidth__';
        $field->labelDescription = '__myself_pageblocks_columns_form_column_maxwidth_desc__';
        $field->min = 0;
        $field->max = 10000;
        $form->addField($field);

        $field = new Select();
        $field->name = 'pageBlockSettings[align]';
        $field->label = '__myself_column_align__';
        $field->labelDescription = '__myself_align_desc__';
        $field->addOption('left', Lang::get('__myself_align_left__'));
        $field->addOption('center', Lang::get('__myself_align_center__'));
        $field->addOption('right', Lang::get('__myself_align_right__'));
        $field->defaultValue = "center";
        $field->getVisibilityCondition()->greatherThan('pageBlockSettings[maxWidth]', '0');
        $form->addField($field);

        $forms[] = $form;

        for ($i = 0; $i <= 5; $i++) {
            $form = new Form();
            $form->id = "column-" . $i;
            $form->label = Lang::get('__myself_pageblocks_columns_form_formlabel__', [$i + 1]);

            $field = new Toggle();
            $field->name = 'pageBlockSettings[column][' . $i . '][enabled]';
            $field->label = '__myself_pageblocks_columns_form_column_enabled__';
            $field->labelDescription = '__myself_pageblocks_columns_form_column_enabled_desc__';
            $form->addField($field);
            $toggleName = $field->name;


            $field = new Number();
            $field->name = 'pageBlockSettings[column][' . $i . '][width]';
            $field->label = '__myself_pageblocks_columns_form_column_width__';
            $field->labelDescription = '__myself_pageblocks_columns_form_column_width_desc__';
            $field->required = true;
            $field->min = 1;
            $field->max = 10000;
            $field->defaultValue = 1;
            $field->getVisibilityCondition()->equal($toggleName, '1');
            $form->addField($field);

            $field = new Number();
            $field->name = 'pageBlockSettings[column][' . $i . '][padding]';
            $field->label = '__myself_pageblocks_columns_form_column_padding__';
            $field->labelDescription = '__myself_pageblocks_columns_form_column_padding_desc__';
            $field->min = 0;
            $field->max = 10000;
            $field->getVisibilityCondition()->equal($toggleName, '1');
            $form->addField($field);

            $field = new Number();
            $field->name = 'pageBlockSettings[column][' . $i . '][minHeight]';
            $field->label = '__myself_pageblocks_minheight__';
            $field->labelDescription = '__myself_pageblocks_minheight_desc__';
            $field->max = 10000;
            $field->getVisibilityCondition()->equal($toggleName, '1');
            $form->addField($field);

            $field = new Color();
            $field->name = 'pageBlockSettings[column][' . $i . '][textColor]';
            $field->label = '__myself_pageblocks_textcolor__';
            $field->getVisibilityCondition()->equal($toggleName, '1');
            $form->addField($field);

            $field = new Color();
            $field->name = 'pageBlockSettings[column][' . $i . '][backgroundColor]';
            $field->label = '__myself_pageblocks_backgroundcolor__';
            $field->getVisibilityCondition()->equal($toggleName, '1');
            $form->addField($field);

            $field = new MediaBrowser();
            $field->name = 'pageBlockSettings[column][' . $i . '][backgroundImage]';
            $field->label = '__myself_pageblocks_backgroundimage__';
            $field->setOnlyImages();
            $field->getVisibilityCondition()->equal($toggleName, '1');
            $form->addField($field);

            $field = new MediaBrowser();
            $field->name = 'pageBlockSettings[column][' . $i . '][backgroundVideo]';
            $field->label = '__myself_pageblocks_backgroundvideo__';
            $field->labelDescription = '__myself_pageblocks_backgroundvideo_desc__';
            $field->setOnlyVideos();
            $field->getVisibilityCondition()->equal($toggleName, '1');
            $form->addField($field);

            $field = new Select();
            $field->name = 'pageBlockSettings[column][' . $i . '][backgroundAlign]';
            $field->label = '__myself_pageblocks_backgroundalign__';
            $field->addOption('top', Lang::get('__myself_align_top__'));
            $field->addOption('center', Lang::get('__myself_align_center__'));
            $field->addOption('bottom', Lang::get('__myself_align_bottom__'));
            $field->defaultValue = "center";
            $field->getVisibilityCondition()
                ->notEmpty('pageBlockSettings[column][' . $i . '][backgroundImage]')
                ->or()
                ->notEmpty('pageBlockSettings[column][' . $i . '][backgroundVideo]');
            $form->addField($field);

            $forms[] = $form;
        }

        return $forms;
    }
}