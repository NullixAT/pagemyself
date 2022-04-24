<?php

namespace Framelix\PageMyself\Themes\Hello;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\PageMyself\Component\ComponentBase;
use Framelix\PageMyself\Storable\WebsiteSettings;
use Framelix\PageMyself\ThemeBase;

/**
 * Hello
 */
class Theme extends ThemeBase
{

    /**
     * Show component content
     * @param ComponentBase $componentInstance
     * @return void
     */
    public function showComponentContent(ComponentBase $componentInstance): void
    {
        $blockSettings = $componentInstance->block->settings;
        $attr = new HtmlAttributes();
        $attr->addClass('component-block-inner');
        $fullWidth = $blockSettings['fullWidth'] ?? null;
        $backgroundColor = $blockSettings['backgroundColor'] ?? null;
        if (!$fullWidth) {
            $attr->setStyle('max-width', 'var(--page-max-width)');
        }
        if ($backgroundColor) {
            $attr->setStyle('background-color', $backgroundColor);
        }
        echo '<div ' . $attr . '>';
        $componentInstance->show();
        echo '</div>';
    }


    /**
     * Show the page content
     */
    public function showContent(): void
    {
        ?>
        <div class="page" style="--page-max-width:<?= ($this->getSettingValue('maxWidth') ?? 900) ?>px;">
            <div class="page-inner">
                <?php
                $this->showNavigation();
                $this->showComponentBlocks('content');
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Add fields to the theme settings form that is displayed when the user click the theme settings icon
     * @param Form $form
     * @return void
     */
    public function addThemeSettingFields(Form $form): void
    {
        $field = new Number();
        $field->name = 'maxWidth';
        $field->label = '__theme_hello_maxwidth_label__';
        $field->labelDescription = '__theme_hello_maxwidth_label_desc__';
        $field->defaultValue = 900;
        $form->addField($field);
    }

    /**
     * Add fields to the component block settings form that is displayed when the user click the component settings icon
     * @param Form $form
     * @param ComponentBase $component
     * @return void
     */
    public function addComponentSettingFields(Form $form, ComponentBase $component): void
    {
        $field = new Toggle();
        $field->name = 'fullWidth';
        $field->label = '__theme_hello_fullwidth_label__';
        $field->labelDescription = '__theme_hello_fullwidth_label_desc__';
        $form->addField($field);

        $field = new Color();
        $field->name = 'backgroundColor';
        $field->label = '__theme_hello_backgroundcolor_label__';
        $form->addField($field);
    }

    /**
     * Get setting value
     * @param string $key
     * @return mixed
     */
    public function getSettingValue(string $key): mixed
    {
        return WebsiteSettings::get('theme_' . $this->themeId . "_" . $key);
    }

}