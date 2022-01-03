<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Myself\LayoutUtils;

use function html_entity_decode;
use function strip_tags;

/**
 * Text page block
 */
class Text extends BlockBase
{
    /**
     * Called before showLayout() to do some preparations if required
     * @return void
     */
    public function beforeShowLayout(): void
    {
        if ($this->pageBlock->pageBlockSettings['textShadow'] ?? null) {
            $style = '';
            $color = $this->pageBlock->pageBlockSettings['textShadowColor'] ?? '#000000';
            $size = $this->pageBlock->pageBlockSettings['textShadowSize'] ?? 10;
            $size = (int)$size;
            $style .= match ($this->pageBlock->pageBlockSettings['textShadowType'] ?? 'around') {
                'hard' => $color . ' 2px 2px 0',
                'soft' => $color . ' 2px 2px 3px',
                default => $color . ' 0 0 ' . $size . 'px',
            };
            $this->htmlAttributes->setStyle('text-shadow', $style);
        }
    }


    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $wysiwyg = $this->pageBlock->pageBlockSettings['wysiwyg'] ?? false;
        LayoutUtils::showLiveEditableText($wysiwyg, $this->pageBlock, "pageBlockSettings", "content");
    }

    /**
     * Get block layout label
     * Will be automatically truncated in editor view when too long
     * @return string
     */
    public function getBlockLayoutLabel(): string
    {
        if (!($this->pageBlock->pageBlockSettings['content'] ?? '')) {
            return parent::getBlockLayoutLabel();
        }
        return strip_tags(html_entity_decode($this->pageBlock->pageBlockSettings['content'] ?? ''));
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
        $form->id = "main";
        $forms[] = $form;

        $field = new Toggle();
        $field->name = 'pageBlockSettings[wysiwyg]';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'pageBlockSettings[textShadow]';
        $form->addField($field);

        $field = new Color();
        $field->name = 'pageBlockSettings[textShadowColor]';
        $field->getVisibilityCondition()->notEmpty('pageBlockSettings[textShadow]');
        $field->defaultValue = "#000000";
        $form->addField($field);

        $field = new Select();
        $field->name = 'pageBlockSettings[textShadowType]';
        $field->getVisibilityCondition()->notEmpty('pageBlockSettings[textShadow]');
        $field->addOption('hard', '__myself_pageblocks_text_textshadowtype_hard__');
        $field->addOption('soft', '__myself_pageblocks_text_textshadowtype_soft__');
        $field->addOption('around', '__myself_pageblocks_text_textshadowtype_around__');
        $field->defaultValue = 'around';
        $form->addField($field);

        $field = new Number();
        $field->name = 'pageBlockSettings[textShadowSize]';
        $field->getVisibilityCondition()
            ->notEmpty('pageBlockSettings[textShadow]')
            ->and()
            ->equal('pageBlockSettings[textShadowType]', 'around');
        $field->defaultValue = 10;
        $form->addField($field);

        return $forms;
    }


}