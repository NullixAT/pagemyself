<?php

namespace Framelix\Myself\Themes;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Utils\ClassUtils;
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
        $htmlClassBase = 'myself-themes-' . strtolower(ClassUtils::getClassBaseName($this->themeSettings->themeClass));
        $navigation = $this->themeSettings->settings['navigation'] ?? 'left';
        $footer = $this->themeSettings->settings['footer'] ?? null;
        $primaryColor = $this->themeSettings->settings['primaryColor'] ?? null;
        if ($primaryColor && !$view->editMode) {
            $hsl = ColorUtils::rgbToHsl(...ColorUtils::hexToRgb($primaryColor));
            $hsl[1] *= 100;
            $hsl[2] *= 100;
            $view->addHeadHtml(
                '<style>
                    .framelix-page, .myself-pageblocks-navigation-popup {
                    --color-primary-hue:' . (int)$hsl[0] . ';
                    --color-primary-text: hsl(var(--color-primary-hue), calc(var(--color-contrast-modifier) + 70%), 40%);  
                    --color-button-default-bg: hsl(var(--color-primary-hue), 20%, 25%);               
                  }
                  </style>'
            );
        }
        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->addClass($htmlClassBase);
        // this will be automatically set to Navigation::LAYOUT after checking available screen size with flip is enabled
        $htmlAttributes->set('data-navigation-layout-inner', 'auto');
        /** @var Navigation $navBlock */
        $navBlock = $this->getFixedPageBlock('nav', Navigation::class)->getLayoutBlock();
        if ($navigation === 'left') {
            $navBlock->layout = Navigation::LAYOUT_VERTICAL_FLIP;
        } elseif ($navigation === 'top') {
            $navBlock->layout = Navigation::LAYOUT_HORIZONTAL;
        }
        echo '<div ' . $htmlAttributes . '>';
        echo '<div class="' . $htmlClassBase . '-sidebar">';
        $navBlock->showLayout();
        echo '</div>';
        echo '<div class="' . $htmlClassBase . '-content">';
        $this->showUserDefinedLayout();
        if ($footer) {
            echo '<div class="' . $htmlClassBase . '-footer">';
            $this->getFixedPageBlock('footer', Text::class)->getLayoutBlock()?->showLayout();
            echo '</div>';
        }
        echo '</div></div>';
        echo '<script>Myself.possibleStickyClasses.push("myself-themes-hello-sidebar")</script>';
    }


    /**
     * Add settings fields to theme settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new Color();
        $field->name = 'primaryColor';
        $field->defaultValue = '#1f74ad';
        $form->addField($field);

        $field = new Select();
        $field->name = 'navigation';
        $field->label = '__myself_nav_align__';
        $field->addOption('left', '__myself_align_left__');
        $field->addOption('top', '__myself_align_top__');
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'footer';
        $form->addField($field);
    }
}