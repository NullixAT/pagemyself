<?php

namespace Framelix\PageMyself\Component;

use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
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
            $data[$file->id] = ['filename' => $file->filename, 'url' => $file->getUrl(), 'id' => $file->id];
        }
        $sort = $this->block->settings['sort'] ?? null;
        $images = [];
        if (is_array($sort)) {
            foreach ($sort as $id) {
                if (isset($data[$id])) {
                    $images[] = $data[$id];
                    unset($data[$id]);
                }
            }
            foreach ($data as $row) {
                $images[] = $row;
            }
        } else {
            $images = array_values($data);
        }
        if ($this->block->settings['random'] ?? null) {
            shuffle($images);
        }
        return [
            'images' => $images,
            'random' => $this->block->settings['random'] ?? null,
            'sort' => $this->block->settings['sort'] ?? null,
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
        $field = new Html();
        $field->name = 'sortInfo';
        $field->label = '';
        $field->defaultValue = '<div class="framelix-alert">' . Lang::get(
                '__pagemyself_component_slideshow_sorting__'
            ) . '</div>';
        $form->addField($field);

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

    /**
     * On api request from frontend
     * @param string $action
     * @param array|null $parameters
     * @return void
     */
    public function onApiRequest(string $action, ?array $parameters): void
    {
        parent::onApiRequest($action, $parameters);
        switch ($action) {
            case 'sort':
                $settings = $this->block->settings;
                $settings['sort'] = $parameters['ids'];
                $this->block->settings = $settings;
                $this->block->store();
                break;
        }
    }


}