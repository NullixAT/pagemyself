<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Myself\LayoutUtils;

/**
 * Text page block
 */
class Text extends BlockBase
{
    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $wysiwyg = $this->pageBlock->pageBlockSettings['wysiwyg'] ?? false;
        $multiline = $this->pageBlock->pageBlockSettings['multiline'] ?? false;
        LayoutUtils::showLiveEditableText($wysiwyg, $multiline, $this->pageBlock, "pageBlockSettings", "content");
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
        $field->name = 'pageBlockSettings[multiline]';
        $field->getVisibilityCondition()->empty('pageBlockSettings[wysiwyg]');
        $form->addField($field);

        return $forms;
    }


}