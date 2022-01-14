<?php

namespace Framelix\Myself\Themes;

use Exception;
use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Myself\BlockLayout\BlockLayoutColumn;
use Framelix\Myself\BlockLayout\BlockLayoutRow;
use Framelix\Myself\BlockLayout\Template;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Storable\PageBlock;
use Framelix\Myself\Storable\ThemeSettings;
use Framelix\Myself\View\Index;

use function basename;
use function class_exists;
use function get_class;
use function strtolower;
use function substr;

/**
 * ThemeBase
 */
abstract class ThemeBase
{
    /**
     * Custom fonts added in theme settings
     * @var array
     */
    public static array $customFonts = [];

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
     * @param ThemeSettings $themeSettings The current theme data
     * @param Page $page The current page
     */
    public function __construct(public ThemeSettings $themeSettings, public Page $page)
    {
    }

    /**
     * Get absolute public folder path on disk
     * @return string
     */
    public function getThemePublicFolderPath(): string
    {
        return FileUtils::getModuleRootPath(ClassUtils::getModuleForClass($this->themeSettings->themeClass))
            . "/public/themes/" . strtolower(ClassUtils::getClassBaseName($this->themeSettings->themeClass));
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
        $themeClass = get_class($this);
        $placement = ClassUtils::getModuleForClass($this) . "_" . $placement;
        $usePageBlock = PageBlock::getByConditionOne(
            'themeClass = {0} && fixedPlacement = {1}',
            [$themeClass, $placement]
        );
        if (!$usePageBlock) {
            $usePageBlock = new PageBlock();
            $usePageBlock->fixedPlacement = $placement;
            $usePageBlock->themeClass = $themeClass;
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
        $blockLayout = $this->page->getBlockLayout();
        $pageBlocks = $this->page->getPageBlocks();
        foreach ($pageBlocks as $id => $pageBlock) {
            if ($pageBlock->fixedPlacement) {
                unset($pageBlocks[$id]);
            }
        }
        if (!$pageBlocks && LayoutUtils::isEditAllowed()) {
            echo '<div class="framelix-alert">' . Lang::get('__myself_info_use_page_templates_empty_page__') . '</div>';
            return;
        }
        $unassignedPageBlocks = $pageBlocks;
        foreach ($blockLayout->rows as $row) {
            echo '<div ' . $this->getRowHtmlAttributes($row) . '>';
            foreach ($row->columns as $column) {
                echo '<div ' . $this->getColumnHtmlAttributes($column) . '>';
                $pageBlock = $pageBlocks[$column->pageBlockId] ?? null;
                if ($pageBlock) {
                    $pageBlock->getLayoutBlock()?->showLayout();
                    unset($unassignedPageBlocks[$pageBlock->id]);
                }
                echo '</div>';
            }
            echo '</div>';
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
     * Get row html attributes
     * @param BlockLayoutRow|null $row
     * @return HtmlAttributes
     */
    public function getRowHtmlAttributes(?BlockLayoutRow $row): HtmlAttributes
    {
        if (!$row) {
            $row = new BlockLayoutRow();
        }
        $rowAttributes = new HtmlAttributes();
        $rowAttributes->addClass('myself-block-layout-row');
        $columns = $row->columns;
        $rowAttributes->set('data-columns', count($columns));
        $rowSettings = $row->settings;
        $settingValue = $rowSettings->gap;
        if ($settingValue) {
            $rowAttributes->setStyle('gap', $rowSettings->gap . "px");
        }
        $settingValue = $rowSettings->maxWidth;
        if ($settingValue) {
            $rowAttributes->setStyle('max-width', $settingValue . "px");
        }
        $settingValue = $rowSettings->alignment;
        if ($settingValue) {
            $rowAttributes->set('data-align', $settingValue);
        }
        $settingValue = $rowSettings->backgroundSize;
        if ($settingValue) {
            $rowAttributes->set('data-background-size', $settingValue);
        }
        $settingValue = $rowSettings->backgroundPosition;
        if ($settingValue) {
            $rowAttributes->set('data-background-position', $settingValue);
        }
        $backgroundImage = MediaFile::getByIdOrDemo($rowSettings->backgroundImage);
        $backgroundVideo = MediaFile::getByIdOrDemo($rowSettings->backgroundVideo);
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
        return $rowAttributes;
    }

    /**
     * Get column html attributes
     * @param BlockLayoutColumn|null $column
     * @return HtmlAttributes
     */
    public function getColumnHtmlAttributes(?BlockLayoutColumn $column): HtmlAttributes
    {
        if (!$column) {
            $column = new BlockLayoutColumn();
        }
        $columnSettings = $column->settings;
        $columnAttributes = new HtmlAttributes();
        $columnAttributes->addClass('myself-block-layout-row-column');
        $settingValue = $columnSettings->padding;
        if ($settingValue) {
            $columnAttributes->setStyle('padding', $settingValue . "px");
        }
        $settingValue = $columnSettings->minWidth;
        if ($settingValue) {
            $columnAttributes->setStyle('min-width', $settingValue . "px");
        }
        $settingValue = $columnSettings->minHeight;
        if ($settingValue) {
            $columnAttributes->setStyle('min-height', $settingValue . "px");
            $verticalTextAlignment = $columnSettings->textVerticalAlignment;
            $textAlignment = $columnSettings->textAlignment;
            if ($verticalTextAlignment && $verticalTextAlignment !== 'top') {
                $columnAttributes->setStyle('display', 'flex');
                if ($verticalTextAlignment === 'center') {
                    $columnAttributes->setStyle('align-items', 'center');
                } elseif ($verticalTextAlignment === 'bottom') {
                    $columnAttributes->setStyle('align-items', 'flex-end');
                }
                if ($textAlignment === 'left') {
                    $columnAttributes->setStyle('justify-content', 'flex-start');
                } elseif ($textAlignment === 'center' || !$textAlignment) {
                    $columnAttributes->setStyle('justify-content', 'center');
                } else {
                    $columnAttributes->setStyle('justify-content', 'flex-end');
                }
            }
        }
        $settingValue = $columnSettings->textColor;
        if ($settingValue) {
            $columnAttributes->setStyle('color', $settingValue);
        }
        $settingValue = $columnSettings->backgroundColor;
        if ($settingValue) {
            $columnAttributes->setStyle('background-color', $settingValue);
        }
        $settingValue = $columnSettings->textAlignment;
        if ($settingValue) {
            $columnAttributes->setStyle('text-align', $settingValue);
        }
        $settingValue = $columnSettings->textSize;
        if ($settingValue) {
            $columnAttributes->setStyle('font-size', $settingValue . "%");
        }
        $settingValue = $columnSettings->backgroundSize;
        if ($settingValue) {
            $columnAttributes->set('data-background-size', $settingValue);
        }
        $settingValue = $columnSettings->backgroundPosition;
        if ($settingValue) {
            $columnAttributes->set('data-background-position', $settingValue);
        }
        $settingValue = $columnSettings->fadeIn;
        if ($settingValue) {
            $columnAttributes->set('data-fade-in', $settingValue);
        }
        $settingValue = $columnSettings->fadeOut;
        if ($settingValue) {
            $columnAttributes->set('data-fade-out', (int)$settingValue);
        }
        $settingValue = $columnSettings->grow;
        if ($settingValue) {
            $columnAttributes->setStyle('flex-grow', $settingValue);
        }
        $backgroundImage = MediaFile::getByIdOrDemo($columnSettings->backgroundImage);
        $backgroundVideo = MediaFile::getByIdOrDemo($columnSettings->backgroundVideo);
        if ($backgroundImage && $backgroundImage->getImageData()) {
            $columnAttributes->set('data-background-media', '1');
            $columnAttributes->set(
                'data-background-image',
                Url::getUrlToFile($backgroundImage->getPath())
            );
        }
        if ($backgroundVideo && $backgroundVideo->getPath()) {
            $columnAttributes->set('data-background-media', '1');
            $columnAttributes->set(
                'data-background-video',
                Url::getUrlToFile($backgroundVideo->getPath())
            );
        }
        return $columnAttributes;
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
     * Add settings fields to theme settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
    }

    /**
     * Set values in the settings from submitted page block settings form
     * @param Form $form
     */
    public function setValuesFromSettingsForm(Form $form): void
    {
        $form->setStorableValues($this->themeSettings);
    }

    /**
     * Get templates for this theme
     * @return Template[]
     */
    public function getTemplates(): array
    {
        $themeFolder = $this->getThemePublicFolderPath();
        $templateFiles = FileUtils::getFiles($themeFolder, "~/template-.*\.json$~");
        $arr = [];
        foreach ($templateFiles as $templateFile) {
            $template = new Template($this, substr(basename($templateFile), 0, -5));
            $arr[$template->templateFilename] = $template;
        }
        return $arr;
    }

    /**
     * Show the theme layout
     * @param Index $view The view where this theme is displayed in
     * @return void
     */
    abstract public function showLayout(Index $view): void;
}