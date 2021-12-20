<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
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
     * Constructor
     * @param PageBlock $pageBlock The corresponding page block for this layout block
     */
    public function __construct(public PageBlock $pageBlock)
    {
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
     * Show content with the surrounding required markup
     * @return void
     */
    final public function showLayout(): void
    {
        $className = ClassUtils::getHtmlClass($this);
        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->addClass('myself-page-block');
        $htmlAttributes->addClass($className);
        $htmlAttributes->set('id', 'pageblock-' . $this->pageBlock);
        $htmlAttributes->set('data-id', $this->pageBlock);
        if ($this->pageBlock->flagDraft) {
            $htmlAttributes->set('data-draft', 1);
        }
        echo '<div ' . $htmlAttributes . '>';
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
     * Get array of settings forms
     * If more then one form is returned, it will create tabs with forms
     * @return Form[]
     */
    public function getSettingsForms(): array
    {
        $form = new Form();
        $form->id = "pageblock";
        $form->label = '__myself_pageblocks_edit_form_internal__';
        $forms = [$form];

        $label = '<div class="framelix-responsive-grid-2"><div>' . Lang::get(
                ClassUtils::getLangKey($this)
            );
        $label .= '</div>';

        if (!$this->pageBlock->fixedPlacement) {
            $label .= '<div style="text-align: right">';
            $label .= '<button class="framelix-button framelix-button-error framelix-button-small myself-delete-page-block" data-page-block-id="' . $this->pageBlock . '" data-icon-left="clear">';
            $label .= Lang::get('__myself_pageblock_edit_internal_delete__');
            $label .= '</button>';
            $label .= '</div>';
        }

        $label .= '</div>';

        $descKey = ClassUtils::getLangKey($this, "desc");

        $field = new Html();
        $field->name = "info";
        $field->label = $label;
        if (Lang::keyExist($descKey)) {
            $field->labelDescription = Lang::get($descKey);
        }
        $form->addField($field);

        if (!$this->pageBlock->fixedPlacement) {
            $field = new Toggle();
            $field->name = "flagDraft";
            $field->label = '__myself_pageblock_edit_internal_draft__';
            $field->labelDescription = '__myself_pageblock_edit_internal_draft_desc_';
            $field->defaultValue = $this->pageBlock->flagDraft;
            $form->addField($field);
        }

        return $forms;
    }

    /**
     * Show content for this block
     * @return void
     */
    abstract public function showContent(): void;
}