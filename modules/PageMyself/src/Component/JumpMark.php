<?php

namespace Framelix\PageMyself\Component;

use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Form;

/**
 * A JumpMark
 */
class JumpMark extends ComponentBase
{
    /**
     * Get default settings for this block
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [];
    }

    /**
     * Show content for this block
     * @return void
     */
    public function show(): void
    {
        echo '<div class="jumpto-' . $this->block . '"></div>';
    }

    /**
     * Add setting fields to the settings form that is displayed when the user click the settings icon
     */
    public function addSettingFields(Form $form): void
    {
        $field = new Html();
        $field->name = 'url';
        $field->label = '__pagemyself_component_jumpmark_link__';
        $field->defaultValue = $this->block->page->getPublicUrl()->setHash('jumpto-' . $this->block);
        $form->addField($field);
    }
}