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
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new Ace();
        $field->name = 'html';
        $field->mode = 'html';
        $form->addField($field);
    }
}