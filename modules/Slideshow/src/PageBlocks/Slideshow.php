<?php

namespace Framelix\Slideshow\PageBlocks;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
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
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        ?>
        <div class="slideshow-pageblocks-slideshow-container myself-lazy-load-parent-anchor"
             data-animation="<?= $this->pageBlock->pageBlockSettings['animation'] ?? 'fade' ?>">
            <div class="framelix-loading"></div>
            <div class="slideshow-pageblocks-slideshow-image-outer">
                <button class="framelix-button slideshow-pageblocks-slideshow-left"
                        data-icon-left="chevron_left"></button>
                <button class="framelix-button slideshow-pageblocks-slideshow-right"
                        data-icon-left="chevron_right"></button>
                <div class="slideshow-pageblocks-slideshow-image"></div>
            </div>
            <?
            if ($this->pageBlock->pageBlockSettings['showImageInfo'] ?? null) {
                ?>
                <div class="slideshow-pageblocks-slideshow-info">
                    <div class="slideshow-pageblocks-slideshow-title"></div>
                </div>
                <?
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
        $storables = Storable::getByIds($this->pageBlock->pageBlockSettings['files'] ?? []);
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

        $field = new Toggle();
        $field->name = 'pageBlockSettings[showImageInfo]';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'pageBlockSettings[automatic]';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'pageBlockSettings[random]';
        $form->addField($field);

        $field = new Select();
        $field->name = 'pageBlockSettings[animation]';
        $field->addOption('fade', '__slideshow_pageblocks_slideshow_animation_fade__');
        $field->addOption('flip', '__slideshow_pageblocks_slideshow_animation_flip__');
        $field->addOption('blur', '__slideshow_pageblocks_slideshow_animation_blur__');
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'pageBlockSettings[files]';
        $field->multiple = true;
        $field->unfoldSelectedFolders = true;
        $field->setOnlyImages();
        $form->addField($field);

        return $forms;
    }
}