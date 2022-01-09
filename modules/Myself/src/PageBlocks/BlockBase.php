<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\Storable\PageBlock;
use Throwable;

use function basename;
use function get_class;
use function str_replace;
use function substr;

/**
 * Page block base
 */
abstract class BlockBase
{
    /**
     * The block html attributes
     * @var HtmlAttributes
     */
    protected HtmlAttributes $htmlAttributes;

    /**
     * Get array if all available page block classes
     * @return string[]
     */
    public static function getAllClasses(): array
    {
        $arr = [];
        foreach (Config::$loadedModules as $module) {
            $files = FileUtils::getFiles(
                FileUtils::getModuleRootPath($module) . "/src/PageBlocks",
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
     * Prepare settings that came from a template and are about to be imported as page layout
     * Should be used to add demo media files/images for example
     * @param array $pageBlockSettings
     */
    public static function prepareTemplateSettingsForImport(array &$pageBlockSettings): void
    {
        // by default, nothing is modified
    }

    /**
     * Prepare settings for template code generator to remove sensible data
     * Should be used to remove settings like media files or non layout settings from the settings array
     * @param array $pageBlockSettings
     */
    public static function prepareTemplateSettingsForExport(array &$pageBlockSettings): void
    {
        // by default, all settings are copied, array is unmodified
    }

    /**
     * Constructor
     * @param PageBlock $pageBlock The corresponding page block for this layout block
     */
    public function __construct(public PageBlock $pageBlock)
    {
        $className = ClassUtils::getHtmlClass($this);
        $this->htmlAttributes = new HtmlAttributes();
        $this->htmlAttributes->addClass('myself-page-block');
        $this->htmlAttributes->addClass($className);
        $this->htmlAttributes->set('id', 'pageblock-' . $this->pageBlock);
        $this->htmlAttributes->set('data-id', $this->pageBlock);
        if ($this->pageBlock->flagDraft) {
            $this->htmlAttributes->set('data-draft', 1);
        }
    }

    /**
     * Show settings form
     * Override if you need additional stuff before the form is shown
     * @param Form $form
     */
    public function showSettingsForm(Form $form): void
    {
        $form->show();
    }

    /**
     * Set values in the settings from submitted page block settings form
     * @param Form $form
     */
    public function setValuesFromSettingsForm(Form $form): void
    {
        $form->setStorableValues($this->pageBlock);
    }

    /**
     * Get an array of key/value config that get passed to the javascript pageblock instance
     * @return array
     */
    public function getJavascriptConfig(): array
    {
        return [];
    }

    /**
     * Called before showLayout() to do some preparations if required
     * @return void
     */
    public function beforeShowLayout(): void
    {
    }

    /**
     * Show content with the surrounding required markup
     * @return void
     */
    final public function showLayout(): void
    {
        $this->beforeShowLayout();
        echo '<div ' . $this->htmlAttributes . '>';
        try {
            $this->showContent();
            $jsClassName = substr(get_class($this), 9);
            $jsClassName = str_replace("\\", "", $jsClassName);
            ?>
            <script>
              (function () {
                if (typeof <?=$jsClassName?> === 'undefined') return
                try {
                  const blockContainer = $('.myself-page-block').filter("[data-id='<?=$this->pageBlock?>']")
                  const instance = new <?=$jsClassName?>(blockContainer, <?=JsonUtils::encode(
                      $this->getJavascriptConfig()
                  )?>)
                  instance.initBlock()
                } catch (e) {
                  console.error(e)
                }
              })()
            </script>
            <?php
        } catch (Throwable $e) {
            LayoutUtils::handleThrowable($e);
        }
        echo '</div>';
    }

    /**
     * Get block layout label
     * Will be automatically truncated in editor view when too long
     * @return string
     */
    public function getBlockLayoutLabel(): string
    {
        return ClassUtils::getLangKey($this->pageBlock->pageBlockClass);
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
    }

    /**
     * Show content for this block
     * @return void
     */
    abstract public function showContent(): void;
}