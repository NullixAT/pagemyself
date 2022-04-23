<?php

namespace Framelix\PageMyself\Themes\Hello;

use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Form;
use Framelix\PageMyself\Storable\WebsiteSettings;
use Framelix\PageMyself\ThemeBase;

/**
 * Hello
 */
class Theme extends ThemeBase
{
    /**
     * Show the page content
     */
    public function showContent(): void
    {
        ?>
        <div class="page">
            <div class="page-inner" style="max-width: <?= $this->getSettingValue('maxWidth') ?? 900 ?>px;">
                <?php
                $this->showNavigation();
                $this->showComponentBlocks('content');
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Add setting fields to the settings form that is displayed when the user click the settings icon
     */
    public function addSettingFields(Form $form): void
    {
        $field = new Number();
        $field->name = 'maxWidth';
        $field->label = '__theme_hello_maxwidth_label__';
        $field->labelDescription = '__theme_hello_maxwidth_label_desc__';
        $field->defaultValue = 900;
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