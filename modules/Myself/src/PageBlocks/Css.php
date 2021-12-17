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
        $field->name = 'pageBlockSettings[css]';
        $field->mode = 'css';
        $form->addField($field);

        return $forms;
    }


}