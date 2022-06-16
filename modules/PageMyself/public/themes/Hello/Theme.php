<?php

namespace Framelix\PageMyself\Themes\Hello;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\PageMyself\Component\ComponentBase;
use Framelix\PageMyself\Form\Field\MediaBrowser;
use Framelix\PageMyself\Storable\MediaFile;
use Framelix\PageMyself\ThemeBase;
use Framelix\PageMyself\View\Index;

use function array_keys;
use function is_array;
use function ucfirst;

/**
 * Hello
 */
class Theme extends ThemeBase
{

    /**
     * The themes default color schemes
     * @var array|string[][]
     */
    public static array $colorSchemes = [
        'Default' => [
            'background' => '#ffffff',
            'font' => '#111',
            'primary' => '#0089ff',
            'header' => '#111',
            'nav' => '#111'
        ],
        'Dark' => [
            'background' => '#111',
            'font' => '#ffffff',
            'primary' => '#0089ff',
            'header' => '#ffffff',
            'nav' => '#ffffff'
        ],
        'Not so dark' => [
            'background' => '#444',
            'font' => '#ffffff',
            'primary' => '#0089ff',
            'header' => '#ffffff',
            'nav' => '#ffffff'
        ],
        'Creamy' => [
            'background' => '#edf6f9',
            'font' => '#002c30',
            'primary' => '#e29578',
            'header' => '#ffddd2',
            'nav' => '#006d77'
        ],
        'Pop' => [
            'background' => 'white',
            'font' => '#073b4c',
            'primary' => '#ef476f',
            'header' => '#ffd166',
            'nav' => '#06d6a0'
        ],
    ];

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
        $backgroundPosition = $blockSettings['backgroundPosition'] ?? 'center';
        $minHeight = $blockSettings['minHeight'] ?? null;
        $backgroundImage = MediaFile::getById($blockSettings['backgroundImage'] ?? null);
        $backgroundVideo = MediaFile::getById($blockSettings['backgroundVideo'] ?? null);
        if ($minHeight) {
            $attr->setStyle('min-height', $minHeight . "vh");
        }
        if (!$fullWidth) {
            $attr->setStyle('max-width', 'var(--page-max-width)');
        } else {
            $attr->addClass('component-block-inner-max-width');
        }
        if ($backgroundColor) {
            $attr->setStyle('background-color', $backgroundColor);
        }
        if ($backgroundImage?->isImage()) {
            $attr->set('data-background-image', $backgroundImage->getUrl());
            $attr->set('data-background-position', $backgroundPosition);
        }
        if ($backgroundVideo?->isVideo()) {
            $attr->set('data-background-video', $backgroundVideo->getUrl());
            $attr->set('data-background-position', $backgroundPosition);
        }
        echo '<div ' . $attr . '>';
        if ($fullWidth) {
            echo '<div style="max-width:var(--page-max-width)">';
        }
        $componentInstance->show();
        if ($fullWidth) {
            echo '</div>';
        }
        echo '</div>';
    }


    /**
     * Show the page content
     */
    public function showContent(): void
    {
        ?>
        <div class="page"
             style="--page-max-width:<?= ($this->getSettingValue('maxWidth') ?? 900) ?>px;">
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
        $field = new Toggle();
        $field->name = 'stickyNav';
        $field->defaultValue = 900;
        $form->addField($field);

        $field = new Number();
        $field->name = 'maxWidth';
        $field->defaultValue = 900;
        $form->addField($field);

        $field = new Number();
        $field->name = 'fontSize';
        $field->defaultValue = 16;
        $form->addField($field);

        $fonts = [
            'Andale Mono' => "andale mono,times,sans-serif",
            'Arial' => "arial,helvetica,sans-serif",
            'Arial Black' => "arial black,avant garde,sans-serif",
            'Book Antiqua' => "book antiqua,palatino,sans-serif",
            'Comic Sans MS' => "comic sans ms,sans-serif",
            'Courier New' => "courier new,courier,sans-serif",
            'Georgia' => "georgia,palatino,sans-serif",
            'Helvetica' => "helvetica,sans-serif",
            'Impact' => "impact,chicago,sans-serif",
            'Symbol' => "symbol,sans-serif",
            'Tahoma' => "tahoma,arial,helvetica,sans-serif",
            'Terminal' => "terminal,monaco,sans-serif",
            'Times New Roman' => "times new roman,times,sans-serif",
            'Trebuchet MS' => "trebuchet ms,geneva,sans-serif",
            'Verdana' => "verdana,geneva,sans-serif",
            'Webdings' => "webdings",
            'Wingdings' => "wingdings,zapf dingbats"
        ];

        $field = new Select();
        $field->name = 'font';
        $field->showResetButton = false;
        $field->defaultValue = $fonts['Arial'];
        foreach ($fonts as $fontName => $fontCss) {
            $field->addOption(
                $fontCss,
                $fontName . ' (<span style="font-family: ' . $fontCss . '">' . $fontName . '</span>)'
            );
        }
        $form->addField($field);

        $field = new Select();
        $field->name = 'colorScheme';
        $field->labelDescription = Lang::get(
            '__theme_hello_colorscheme_desc__',
            ['<a href="https://coolors.co/palettes/trending" target="_blank">https://coolors.co/palettes/trending</a>']
        );
        $field->showResetButton = false;
        foreach (self::$colorSchemes as $colorScheme => $row) {
            $html = '<div style="display: flex; align-items: center; gap: 5px; padding:10px; border-radius: var(--border-radius); color:white; background:#414645 linear-gradient(45deg, #262928 25%, #414645 25%, #414645 50%, #262928 50%, #262928 75%, #414645 75%, #414645 100%); background-size: 56px 56px;">';
            $html .= '<b style="min-width: 100px">' . $colorScheme . '</b>';
            foreach ($row as $colorName => $defaultColor) {
                $html .= '<div style="cursor:pointer; border-radius:3px; height:30px; width:30px; box-shadow:rgba(0,0,0,0.5) 0 0 3px; background-color:' . $defaultColor . ';" title="' . Lang::get(
                        '__theme_hello_colorscheme_' . $colorName . '__'
                    ) . '">';
                $html .= '</div>';
            }
            $html .= '</div>';
            $field->addOption($colorScheme, $html);
        }
        $field->defaultValue = 'Default';
        $form->addField($field);

        $colorNames = array_keys(reset(self::$colorSchemes));
        foreach ($colorNames as $colorName) {
            $field = new Color();
            $field->name = 'colorSchemeOverride' . ucfirst($colorName);
            $field->label = Lang::get(
                '__theme_hello_colorscheme_override__',
                [Lang::get('__theme_hello_colorscheme_' . $colorName . '__')]
            );

            $form->addField($field);
        }
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
        $form->addField($field);

        $field = new Color();
        $field->name = 'backgroundColor';
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundImage';
        $field->setOnlyImages();
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundVideo';
        $field->setOnlyVideos();
        $form->addField($field);

        $form->addFieldGroup(
            'bg',
            Lang::get('__theme_hello_more_settings__'),
            ['fullWidth', 'minHeight', 'backgroundPosition', 'backgroundColor', 'backgroundImage', 'backgroundVideo'],
            false
        );

        $field = new Toggle();
        $field->name = 'fullWidth';
        $form->addField($field);

        $field = new Number();
        $field->name = 'minHeight';
        $field->min = 0;
        $field->max = 100;
        $form->addField($field);

        $field = new Select();
        $field->name = 'backgroundPosition';
        $field->addOption('top', Lang::get('__theme_hello_backgroundposition_top__'));
        $field->addOption('center', Lang::get('__theme_hello_backgroundposition_center__'));
        $field->addOption('bottom', Lang::get('__theme_hello_backgroundposition_bottom__'));
        $field->defaultValue = "center";
        $form->addField($field);

        $field = new Color();
        $field->name = 'backgroundColor';
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundImage';
        $field->setOnlyImages();
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'backgroundVideo';
        $field->setOnlyVideos();
        $form->addField($field);
    }

    /**
     * On view setup
     * Use this to insert <head> data with $view->addHeadHtmlAfterInit() if you need some additional initializing stuff
     * @param Index $view
     * @return void
     */
    public function onViewSetup(Index $view): void
    {
        $colorScheme = $this->getSettingValue('colorScheme') ?? 'Default';
        $colors = self::$colorSchemes[$colorScheme] ?? 'Default';

        $colorMap = [];

        $headHtml = '<style>';
        $headHtml .= ':root{';
        foreach ($colors as $colorName => $color) {
            $varnames = match ($colorName) {
                "background" => "--color-page-bg",
                "font" => "--color-page-text",
                "primary" => ["--color-primary-text", "--color-primary-bg", "--color-button-primary-bg"],
                "header" => "--color-header",
                "nav" => "--nav-color-text",
                default => "--undefined"
            };
            $value = $color;
            if ($overrideValue = $this->getSettingValue('colorSchemeOverride' . ucfirst($colorName))) {
                $value = $overrideValue;
            }
            if (!is_array($varnames)) {
                $varnames = [$varnames];
            }
            foreach ($varnames as $varname) {
                $headHtml .= $varname . ":" . $value . ";";
            }
            $colorMap[] = $value;
            $colorMap[] = $colorName;
        }
        $font = ($this->getSettingValue('font') ?? 'arial, sans-serif');
        $fontSize = $this->getSettingValue('fontSize');
        if (!$fontSize) {
            $fontSize = "16px";
        } else {
            $fontSize .= "px";
        }
        $headHtml .= '--font: ' . $font . '; font-family: ' . $font . "; font-size: " . $fontSize . "; --font-size: " . $fontSize . ";";
        $headHtml .= '}';
        $headHtml .= '</style>';
        if ($colorMap) {
            $headHtml .= '<script>PageMyselfComponent.additionalColorMap = ' . JsonUtils::encode(
                    $colorMap
                ) . ';</script>';
        }
        $view->addHeadHtmlAfterInit($headHtml);
    }


}