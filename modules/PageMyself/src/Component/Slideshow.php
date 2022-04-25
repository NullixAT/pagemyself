<?php

namespace Framelix\PageMyself\Component;

use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\PageMyself\Form\Field\MediaBrowser;
use Framelix\PageMyself\Storable\MediaFile;
use Framelix\PageMyself\Storable\MediaFolder;
use function shuffle;

/**
 * A image slideshow
 */
class Slideshow extends ComponentBase
{

    /**
     * Get javascript init parameters
     * @return array|null
     */
    public function getJavascriptInitParameters(): ?array
    {
        $selectedValues = Storable::getByIds($this->block->settings['images'] ?? null);
        $files = [];
        if ($selectedValues) {
            foreach ($selectedValues as $selectedValue) {
                if ($selectedValue instanceof MediaFile) {
                    $files[$selectedValue->id] = $selectedValue;
                } elseif ($selectedValue instanceof MediaFolder) {
                    $files = ArrayUtils::merge($files, $selectedValue->getAllChildFiles());
                }
            }
        }
        $data = [];
        foreach ($files as $file) {
            if (!$file->isImageFile()) {
                continue;
            }
            $data[] = ['filename' => $file->filename, 'url' => $file->getUrl()];
        }
        if ($this->block->settings['random'] ?? null) {
            shuffle($data);
        }
        return [
            'images' => $data,
            'random' => $this->block->settings['random'] ?? null,
            'thumbnails' => $this->block->settings['thumbnails'] ?? null
        ];
    }

    /**
     * Show content for this block
     * @return void
     */
    public function show(): void
    {
        ?>
        <div class="slideshow-container">
            <div class="slideshow-image-container">
                <button class="framelix-button framelix-button-primary slideshow-btn" data-dir="-1"
                        data-icon-left="chevron_left">

                </button>
                <div class="slideshow-image"></div>
                <button class="framelix-button framelix-button-primary slideshow-btn" data-dir="1"
                        data-icon-left="chevron_right">
                </button>
            </div>
            <div class="slideshow-thumbs">

            </div>
        </div>
        <?php
    }

    /**
     * Add setting fields to the settings form that is displayed when the user click the settings icon
     */
    public function addSettingFields(Form $form): void
    {
        $field = new MediaBrowser();
        $field->name = 'images';
        $field->label = '__pagemyself_component_slideshow_' . strtolower($field->name) . '__';
        $field->multiple = true;
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'thumbnails';
        $field->label = '__pagemyself_component_slideshow_' . strtolower($field->name) . '__';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'random';
        $field->label = '__pagemyself_component_slideshow_' . strtolower($field->name) . '__';
        $form->addField($field);
    }
}