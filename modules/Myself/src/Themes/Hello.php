<?php

namespace Framelix\Myself\Themes;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Utils\ColorUtils;
use Framelix\Myself\PageBlocks\Navigation;
use Framelix\Myself\PageBlocks\Text;
use Framelix\Myself\View\Index;

use function strtolower;

/**
 * Hello Theme
 */
class Hello extends ThemeBase
{
    /**
     * Show the theme layout
     * @param Index $view The view where this theme is displayed in
     * @return void
     */
    public function showLayout(Index $view): void
    {
        $htmlClassBase = 'myself-themes-' . strtolower($this->theme->name);
        $navigation = $this->theme->settings['navigation'] ?? 'left';
        $footer = $this->theme->settings['footer'] ?? null;
        $primaryColor = $this->theme->settings['primaryColor'] ?? null;
        if ($primaryColor && !$view->editMode) {
            $hsl = ColorUtils::rgbToHsl(...ColorUtils::hexToRgb($primaryColor));
            $hsl[1] *= 100;
            $hsl[2] *= 100;
            $view->addHeadHtml(
                '<style>:root{            
              --color-primary-hue:' . (int)$hsl[0] . ';
              --color-primary-bg-strong: hsl(' . (int)$hsl[0] . ', calc(var(--color-contrast-modifier) + ' . (int)$hsl[1] . '%), ' . (int)$hsl[2] . '%);
              --color-primary-bg-strong-text: white;
              --color-primary-bg-subtle: hsl(' . (int)$hsl[0] . ', calc(var(--color-contrast-modifier) + ' . (int)$hsl[1] . '%), 90%);
              --color-primary-bg-subtle-text: var(--color-page-text);
              --color-primary-text: hsl(' . (int)$hsl[0] . ', calc(var(--color-contrast-modifier) + ' . (int)$hsl[1] . '%), 40%);   
            }</style>'
            );
        }
        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->addClass($htmlClassBase);
        $htmlAttributes->set('data-navigation', $navigation);

        echo '<div ' . $htmlAttributes . '>';
        echo '<div class="' . $htmlClassBase . '-sidebar">';
        $this->getFixedPageBlock('nav', Navigation::class)->getLayoutBlock()?->showLayout();
        echo '<button class="framelix-button framelix-button-trans ' . $htmlClassBase . '-more" data-icon-left="menu"></button>';
        echo '</div>';
        echo '<div class="' . $htmlClassBase . '-content">';
        $this->showUserDefinedLayout();
        if ($footer) {
            echo '<div class="' . $htmlClassBase . '-footer">';
            $this->getFixedPageBlock('footer', Text::class)->getLayoutBlock()?->showLayout();
            echo '</div>';
        }
        echo '</div></div>';
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

        $field = new Color();
        $field->name = 'settings[primaryColor]';
        $field->defaultValue = '#1f74ad';
        $form->addField($field);

        $field = new Select();
        $field->name = 'settings[navigation]';
        $field->label = '__myself_nav_align__';
        $field->addOption('left', '__myself_align_left__');
        $field->addOption('top', '__myself_align_top__');
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'settings[footer]';
        $form->addField($field);

        return $forms;
    }


}