<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\Form\Field\Ace;
use Framelix\Myself\LayoutUtils;

use function trim;

/**
 * Css page block
 */
class Css extends BlockBase
{
    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        if (LayoutUtils::isEditAllowed()) {
            ?>
            <div class="framelix-alert myself-show-if-editmode" title="__myself_visible_in_editmode__">CSS Block</div>
            <?php
        }
        $css = $this->pageBlock->pageBlockSettings['css'] ?? '';
        if (!trim($css)) {
            return;
        }
        ?>
        <script>$('head').append($('<style>').html(<?=JsonUtils::encode($css)?>))</script>
        <?
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new Ace();
        $field->name = 'css';
        $field->mode = 'css';
        $form->addField($field);
    }


}