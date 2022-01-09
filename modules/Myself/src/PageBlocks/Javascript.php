<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Form;
use Framelix\Myself\Form\Field\Ace;
use Framelix\Myself\LayoutUtils;

/**
 * Javascript page block
 */
class Javascript extends BlockBase
{
    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        if (LayoutUtils::isEditAllowed()) {
            ?>
            <div class="framelix-alert myself-show-if-editmode" title="__myself_visible_in_editmode__">Javascript
                Block
            </div>
            <?php
        }
        $js = $this->pageBlock->pageBlockSettings['javascript'] ?? '';
        if (!trim($js)) {
            return;
        }
        ?>
        <script>
          try {
              <? echo $js ?>
          } catch (e) {
            console.error("ERROR on running page block #<?=$this->pageBlock?>")
            console.error(e)
          }
        </script>
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
        $field->name = 'javascript';
        $field->mode = 'javascript';
        $form->addField($field);
    }
}