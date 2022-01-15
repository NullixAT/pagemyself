<?php

namespace Framelix\Slideshow\PageBlocks;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\Storable;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\MediaFile;

use function array_values;
use function shuffle;

/**
 * Slideshow page block
 */
class Slideshow extends BlockBase
{
    /**
     * Prepare settings for template code generator to remove sensible data
     * Should be used to remove settings like media files or non layout settings from the settings array
     * @param array $pageBlockSettings
     */
    public static function prepareTemplateSettingsForExport(array &$pageBlockSettings): void
    {
        // reset files with demo image
        $pageBlockSettings['files'] = 'demo';
    }

    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        ?>
        <div class="slideshow-pageblocks-slideshow-container myself-lazy-load-parent-anchor"
             data-animation="<?= $this->pageBlock->pageBlockSettings['animation'] ?? 'fade' ?>">
            <div class="slideshow-pageblocks-slideshow-image-outer">
                <button aria-label="<?= Lang::get(
                    '__slideshow_pageblocks_prev_image__'
                ) ?>" class="framelix-button slideshow-pageblocks-slideshow-left"
                        data-icon-left="chevron_left"></button>
                <button aria-label="<?= Lang::get(
                    '__slideshow_pageblocks_next_image__'
                ) ?>" class="framelix-button slideshow-pageblocks-slideshow-right"
                        data-icon-left="chevron_right"></button>
                <div class="slideshow-pageblocks-slideshow-image"></div>
            </div>
            <?php
            if ($this->pageBlock->pageBlockSettings['showImageInfo'] ?? null) {
                ?>
                <div class="slideshow-pageblocks-slideshow-info">
                    <div class="slideshow-pageblocks-slideshow-title"></div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Get an array of key/value config that get passed to the javascript pageblock instance
     * @return array
     */
    public function getJavascriptConfig(): array
    {
        if (($this->pageBlock->pageBlockSettings['files'] ?? '') === 'demo') {
            $storables = [MediaFile::getByIdOrDemo('demo-image')];
        } else {
            $storables = Storable::getByIds($this->pageBlock->pageBlockSettings['files'] ?? []);
        }
        $imageData = MediaFile::getFlatListOfImageDataRecursive($storables);
        if ($this->pageBlock->pageBlockSettings['random'] ?? null) {
            shuffle($imageData);
        }
        return [
            'images' => array_values($imageData),
            'automatic' => $this->pageBlock->pageBlockSettings['automatic'] ?? false
        ];
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new Toggle();
        $field->name = 'showImageInfo';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'automatic';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'random';
        $form->addField($field);

        $field = new Select();
        $field->name = 'animation';
        $field->addOption('fade', '__slideshow_pageblocks_slideshow_animation_fade__');
        $field->addOption('flip', '__slideshow_pageblocks_slideshow_animation_flip__');
        $field->addOption('blur', '__slideshow_pageblocks_slideshow_animation_blur__');
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'files';
        $field->multiple = true;
        $field->unfoldSelectedFolders = true;
        $field->setOnlyImages();
        $form->addField($field);
    }
}