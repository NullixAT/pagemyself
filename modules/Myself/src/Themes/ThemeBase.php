<?php

namespace Framelix\Myself\Themes;

use Exception;
use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;
use Framelix\Myself\Storable\Theme;
use Framelix\Myself\Storable\WebsiteSettings;
use Framelix\Myself\View\Index;

use function basename;
use function class_exists;
use function is_array;

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
            $usePageBlock->store();
        }
        return $usePageBlock;
    }

    /**
     * Show all user defined layout and blocks
     * @return void
     */
    public function showUserDefinedLayout(): void
    {
        $config = WebsiteSettings::get('blockLayout');
        $pageBlocks = $this->page->getPageBlocks();
        foreach ($pageBlocks as $id => $pageBlock) {
            if ($pageBlock->fixedPlacement) {
                unset($pageBlocks[$id]);
            }
        }
        $unassignedPageBlocks = $pageBlocks;
        if (is_array($config['rows'] ?? null)) {
            foreach ($config['rows'] as $row) {
                $rowAttributes = new HtmlAttributes();
                $rowAttributes->addClass('myself-block-layout-row');
                $columns = $row['columns'] ?? null;
                $rowSettings = $row['settings'] ?? null;
                $settingValue = $rowSettings['gap'] ?? null;
                if ($settingValue) {
                    $rowAttributes->setStyle('gap', NumberUtils::toFloat($settingValue) . "px");
                }
                $settingValue = $rowSettings['maxWidth'] ?? null;
                if ($settingValue) {
                    $rowAttributes->setStyle('max-width', NumberUtils::toFloat($settingValue) . "px");
                }
                $settingValue = $rowSettings['alignment'] ?? null;
                if ($settingValue) {
                    $rowAttributes->set('data-align', $settingValue);
                }
                $settingValue = $rowSettings['backgroundSize'] ?? null;
                if ($settingValue) {
                    $rowAttributes->set('data-background-size', $settingValue);
                }
                $backgroundImage = MediaFile::getById($rowSettings['backgroundImage'] ?? null);
                $backgroundVideo = MediaFile::getById($rowSettings['backgroundVideo'] ?? null);
                if ($backgroundImage && $backgroundImage->getImageData()) {
                    $rowAttributes->set(
                        'data-background-image',
                        Url::getUrlToFile($backgroundImage->getPath())
                    );
                }
                if ($backgroundVideo && $backgroundVideo->getPath()) {
                    $rowAttributes->set(
                        'data-background-video',
                        Url::getUrlToFile($backgroundVideo->getPath())
                    );
                }
                echo '<div ' . $rowAttributes . '>';
                if (is_array($columns)) {
                    foreach ($columns as $columnRow) {
                        $columnSettings = $columnRow['settings'] ?? null;
                        $columnAttributes = new HtmlAttributes();
                        $columnAttributes->addClass('myself-block-layout-row-column');
                        $settingValue = $columnSettings['padding'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('padding', NumberUtils::toFloat($settingValue) . "px");
                        }
                        $settingValue = $columnSettings['minWidth'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('min-width', NumberUtils::toFloat($settingValue) . "px");
                        }
                        $settingValue = $columnSettings['minHeight'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('min-height', NumberUtils::toFloat($settingValue) . "px");
                        }
                        $settingValue = $columnSettings['textColor'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('color', $settingValue);
                        }
                        $settingValue = $columnSettings['backgroundColor'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('background-color', $settingValue);
                        }
                        $settingValue = $columnSettings['textAlignment'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('text-align', $settingValue);
                        }
                        $settingValue = $columnSettings['textSize'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('font-size', $settingValue . "%");
                        }
                        $settingValue = $columnSettings['backgroundSize'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->set('data-background-size', $settingValue);
                        }
                        $settingValue = $columnSettings['grow'] ?? null;
                        if ($settingValue) {
                            $columnAttributes->setStyle('flex-grow', $settingValue);
                        }
                        $backgroundImage = MediaFile::getById($columnSettings['backgroundImage'] ?? null);
                        $backgroundVideo = MediaFile::getById($columnSettings['backgroundVideo'] ?? null);
                        if ($backgroundImage && $backgroundImage->getImageData()) {
                            $columnAttributes->set(
                                'data-background-image',
                                Url::getUrlToFile($backgroundImage->getPath())
                            );
                        }
                        if ($backgroundVideo && $backgroundVideo->getPath()) {
                            $columnAttributes->set(
                                'data-background-video',
                                Url::getUrlToFile($backgroundVideo->getPath())
                            );
                        }
                        echo '<div ' . $columnAttributes . '>';
                        $pageBlock = $pageBlocks[$columnRow['pageBlockId'] ?? 0] ?? null;
                        if ($pageBlock) {
                            $pageBlock->getLayoutBlock()?->showLayout();
                            unset($unassignedPageBlocks[$pageBlock->id]);
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                }
            }
        }
        foreach ($unassignedPageBlocks as $pageBlock) {
            $rowAttributes = new HtmlAttributes();
            $rowAttributes->addClass('myself-block-layout-row');
            $columnAttributes = new HtmlAttributes();
            $columnAttributes->addClass('myself-block-layout-row-column');
            echo '<div ' . $rowAttributes . '><div ' . $columnAttributes . '>';
            $pageBlock->getLayoutBlock()?->showLayout();
            echo '</div></div>';
        }
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