<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Form;
use Framelix\Myself\Form\Field\Ace;
use Framelix\Myself\LayoutUtils;

/**
 * Html page block
 */
class Html extends BlockBase
{
    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $html = $this->pageBlock->pageBlockSettings['html'] ?? '';
        if ($html) {
            echo $html;
        } elseif (LayoutUtils::isEditAllowed()) {
            ?>
            <div class="framelix-alert myself-show-if-editmode" title="__myself_visible_in_editmode__">HTML Block</div>
            <?php
        }
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

        $field = new Ace();
        $field->name = 'pageBlockSettings[html]';
        $field->mode = 'html';
        $form->addField($field);

        return $forms;
    }
}