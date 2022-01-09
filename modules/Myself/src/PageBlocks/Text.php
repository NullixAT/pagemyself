<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Myself\LayoutUtils;

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
        LayoutUtils::showLiveEditableText($this->pageBlock, "pageBlockSettings", "content");
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
        return $this->pageBlock->pageBlockSettings['content'];
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new Toggle();
        $field->name = 'textShadow';
        $form->addField($field);

        $field = new Color();
        $field->name = 'textShadowColor';
        $field->getVisibilityCondition()->notEmpty('textShadow');
        $field->defaultValue = "#000000";
        $form->addField($field);

        $field = new Select();
        $field->name = 'textShadowType';
        $field->getVisibilityCondition()->notEmpty('textShadow');
        $field->addOption('hard', '__myself_pageblocks_text_textshadowtype_hard__');
        $field->addOption('soft', '__myself_pageblocks_text_textshadowtype_soft__');
        $field->addOption('around', '__myself_pageblocks_text_textshadowtype_around__');
        $field->defaultValue = 'around';
        $form->addField($field);

        $field = new Number();
        $field->name = 'textShadowSize';
        $field->getVisibilityCondition()
            ->notEmpty('textShadow')
            ->and()
            ->equal('textShadowType', 'around');
        $field->defaultValue = 10;
        $form->addField($field);
    }


}