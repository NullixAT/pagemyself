<?php

namespace Framelix\Myself\Themes;

use Exception;
use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Myself\Form\Field\Ace;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;
use Framelix\Myself\Storable\Theme;
use Framelix\Myself\View\Index;

use function basename;
use function class_exists;

/**
 * ThemeBase
 */
abstract class ThemeBase
{
    /**
     * Create instance from given class name
     * @param string|null $className
     * @return static|null Null if class is not a valid ThemeBase class
     */
    public static function createFromClassName(?string $className): ?self
    {
        if (!$className || !class_exists($className)) {
            return null;
        }
        $instance = new $className();
        if ($instance instanceof ThemeBase) {
            return $instance;
        }
        return null;
    }

    /**
     * Get array if all available page block classes
     * @return string[]
     */
    public static function getAllClasses(): array
    {
        $arr = [];
        foreach (Config::$loadedModules as $module) {
            $files = FileUtils::getFiles(
                FileUtils::getModuleRootPath($module) . "/src/Themes",
                "~\.php$~i",
                true
            );
            foreach ($files as $file) {
                $className = ClassUtils::getClassNameForFile($file);
                if (basename($file) === 'BlockBase.php') {
                    continue;
                }
                $arr[] = $className;
            }
        }
        return $arr;
    }


    /**
     * Constructor
     * @param Theme $theme The current theme data
     * @param Page $page The current page
     */
    public function __construct(public Theme $theme, public Page $page)
    {
    }

    /**
     * This function is called before the layout is generated
     * Use this to setup some meta stuff
     * @param Index $index
     */
    public function viewSetup(Index $index): void
    {
        $settings = $this->theme->settings;
        $favicon = MediaFile::getById($settings['favicon'] ?? null);
        $imageData = $favicon?->getImageData();
        if ($imageData) {
            $index->addHeadHtml('<link rel="icon" href="' . $imageData['sizes']['original']['url'] . '">');
        }
    }

    /**
     * Get fixed page block that is a fixed part of this theme
     * Mostly used for header/footer/sidebar/etc...
     * @param string $placement Exmaple: header, footer, sidebar, etc...
     * @param string $typeClass
     * @return PageBlock
     */
    public function getFixedPageBlock(string $placement, string $typeClass): PageBlock
    {
        if (!class_exists($typeClass)) {
            $type = new $typeClass();
            if (!($type instanceof ThemeBase)) {
                throw new Exception("$typeClass is not an instance of ThemeBase in " . __METHOD__);
            }
        }
        $placement = ClassUtils::getModuleForClass($this) . "_" . $placement;
        $usePageBlock = PageBlock::getByConditionOne('theme = {0} && fixedPlacement = {1}', [$this->theme, $placement]);
        if (!$usePageBlock) {
            $usePageBlock = new PageBlock();
            $usePageBlock->fixedPlacement = $placement;
            $usePageBlock->theme = $this->theme;
            $usePageBlock->flagDraft = false;
            $usePageBlock->pageBlockClass = $typeClass;
            $usePageBlock->sort = 0;
            $usePageBlock->store();
        }
        return $usePageBlock;
    }

    /**
     * Show all user defined page blocks
     * @return void
     */
    public function showUserDefinedPageBlocks(): void
    {
        ?>
        <div class="myself-page-blocks">
            <?
            $pageBlocks = $this->page->getPageBlocks();
            foreach ($pageBlocks as $pageBlock) {
                if ($pageBlock->fixedPlacement) {
                    continue;
                }
                $pageBlock->getLayoutBlock()?->showLayout();
            }
            ?>
        </div>
        <?
    }

    /**
     * Show the settings form
     * Override if you need additional stuff before the form is shown
     * @param Form $form
     */
    public function showSettingsForm(Form $form): void
    {
        $form->show();
    }

    /**
     * Get array of settings forms
     * If more then one form is returned, it will create tabs with forms
     * @return Form[]
     */
    public function getSettingsForms(): array
    {
        $form = new Form();
        $form->id = "themesettings";
        $form->label = '__myself_theme_settings_form_internal__';
        $forms = [$form];

        $field = new Html();
        $field->name = "info";
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'settings[favicon]';
        $field->label = '__myself_theme_settings_form_internal_favicon__';
        $field->labelDescription = '__myself_theme_settings_form_internal_favicon_desc__';
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Ace();
        $field->label = '__myself_theme_settings_form_internal_pagejs__';
        $field->labelDescription = '__myself_theme_settings_form_internal_pagejs_desc__';
        $field->name = 'settings[pagejs]';
        $field->mode = 'javascript';
        $field->initialHidden = true;
        $form->addField($field);

        $field = new Ace();
        $field->label = '__myself_theme_settings_form_internal_pagecss__';
        $field->labelDescription = '__myself_theme_settings_form_internal_pagecss_desc__';
        $field->name = 'settings[pagecss]';
        $field->mode = 'css';
        $field->initialHidden = true;
        $form->addField($field);

        return $forms;
    }

    /**
     * Set values in the settings from submitted page block settings form
     * @param Form $form
     */
    public function setValuesFromSettingsForm(Form $form): void
    {
        $form->setStorableValues($this->theme);
    }

    /**
     * Show the theme layout
     * @param Index $view The view where this theme is displayed in
     * @return void
     */
    abstract public function showLayout(Index $view): void;
}