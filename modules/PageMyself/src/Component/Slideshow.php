<?php

namespace Framelix\PageMyself\Component;

use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\PageMyself\Form\Field\MediaBrowser;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\MediaFile;

use function shuffle;

/**
 * An image slideshow
 */
class Slideshow extends ComponentBase
{

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'saveImageData':
                $block = ComponentBlock::getById(Request::getGet('blockId'));
                $settings = $block->settings ?? [];
                $settings['imageData'] = ArrayUtils::merge(
                    $settings['imageData'] ?? null,
                    $jsCall->parameters['imageData']['data']
                );
                $settings['imageSort'] = $jsCall->parameters['imageSort'];
                $block->settings = $settings;
                $block->store();
                break;
            case 'editImageData':
                $block = ComponentBlock::getById(Request::getGet('blockId'));
                $settings = $block->settings ?? [];
                $images = MediaFile::getFlatList($jsCall->parameters['images'] ?? null);
                $imageArr = [];
                if ($settings['imageSort'] ?? null) {
                    foreach ($settings['imageSort'] as $imageId) {
                        $image = MediaFile::getById($imageId);
                        if ($image && isset($images[$imageId])) {
                            $imageArr[$image->id] = $image;
                        }
                    }
                }

                $imageArr = ArrayUtils::merge($imageArr, $images);

                ArrayUtils::sort($settings, 'sort', [SORT_ASC, SORT_NUMERIC]);
                echo '<div class="image-data-editor-entries">';
                foreach ($imageArr as $image) {
                    $settingsRow = $settings['imageData'][$image->id] ?? null;
                    ?>
                    <div class="image-data-editor-entry" data-id="<?= $image->id ?>">
                        <div>
                            <img src="<?= $image->getUrl(100) ?>" alt="<?= $image->filename ?>">
                        </div>
                        <div style="flex: 1 1 auto">
                            <div><input name="data[<?= $image->id ?>][title]" class="framelix-form-field-input"
                                        type="text" placeholder="<?= Lang::get(
                                    '__pagemyself_component_slideshow_editimagedata_title__'
                                ) ?>" value="<?= HtmlUtils::escape($settingsRow['title'] ?? '') ?>"></div>
                            <div><textarea name="data[<?= $image->id ?>][description]" class="framelix-form-field-input"
                                           placeholder="<?= Lang::get(
                                               '__pagemyself_component_slideshow_editimagedata_description__'
                                           ) ?>"><?= HtmlUtils::escape($settingsRow['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div>
                            <button class="framelix-button framelix-button-small sort-image-down framelix-button-customcolor"
                                    data-icon-left="south" style="--color-custom-bg:#2190af; --color-custom-text:white;"
                                    title="__pagemyself_component_sort_down__"></button>
                            <button class="framelix-button framelix-button-small sort-image-up framelix-button-customcolor"
                                    data-icon-left="north" style="--color-custom-bg:#216daf; --color-custom-text:white;"
                                    title="__pagemyself_component_sort_up__"></button>
                        </div>
                    </div>
                    <?php
                }
                echo '</div>';
                ?>
                <button class="framelix-button close-image-data-editor"><?= Lang::get("__framelix_close__") ?></button>
                <script>
                  (function () {
                    function saveData () {
                      const ids = []
                      $('.image-data-editor-entry').each(function () {
                        ids.push($(this).attr('data-id'))
                      })
                      FramelixApi.callPhpMethod(<?=JsonUtils::encode(
                          JsCall::getCallUrl(__CLASS__, 'saveImageData', ['blockId' => $block->id])
                      )?>, { 'imageData': FormDataJson.toJson(container), 'imageSort': ids })
                    }

                    const container = $('.image-data-editor-entries')
                    container.on('click', '.sort-image-up', function () {
                      const entry = $(this).closest('.image-data-editor-entry')
                      entry.prev().before(entry)
                      saveData()
                    })
                    container.on('click', '.sort-image-down', function () {
                      const entry = $(this).closest('.image-data-editor-entry')
                      entry.next().after(entry)
                      saveData()
                    })
                    container.on('change', function () {
                      saveData()
                    })
                    $('.close-image-data-editor').on('click', function () {
                      saveData()
                      FramelixModal.currentInstance.destroy()
                    })
                  })()
                </script>
                <style>
                  .image-data-editor-entry {
                    display: flex;
                    gap: 10px;
                    padding: 5px;
                    background: white;
                    margin-bottom: 10px;
                  }
                  .image-data-editor-entry img {
                    width: 100px;
                    max-width: 10vw;
                  }

                  .image-data-editor-entry input,
                  .image-data-editor-entry textarea {
                    margin-bottom: 3px;

                  }
                </style>
                <?php

                break;
        }
    }

    /**
     * Get javascript init parameters
     * @return array|null
     */
    public function getJavascriptInitParameters(): ?array
    {
        $files = MediaFile::getFlatList($this->block->settings['images'] ?? null);
        $data = [];
        $imageData = $this->block->settings['imageData'] ?? null;
        foreach ($files as $file) {
            if (!$file->isImage()) {
                continue;
            }
            $imageDataRow = $imageData[$file->id] ?? null;
            $data[$file->id] = [
                'filename' => $file->filename,
                'url' => $file->getUrl(),
                'id' => $file->id,
                'title' => $imageDataRow['title'] ?? null,
                'description' => $imageDataRow['description'] ?? null
            ];
        }
        $sort = $this->block->settings['imageSort'] ?? null;
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
            'thumbnails' => $this->block->settings['thumbnails'] ?? null,
            ''
        ];
    }

    /**
     * Show content for this block
     * @return void
     */
    public function show(): void
    {
        $settings = $this->block->settings;
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
            <?php
            if ($settings['showTitle'] ?? null) {
                ?>
                <div class="slideshow-title"></div>
                <?php
            }
            if ($settings['showDescription'] ?? null) {
                ?>
                <div class="slideshow-description"></div>
                <?php
            }
            if ($settings['thumbnails'] ?? null) {
                ?>
                <div class="slideshow-thumbs"></div>
                <?php
            }
            ?>
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

        $field = new Html();
        $field->name = 'editImageData';
        $field->label = '';
        $field->defaultValue = '<button class="framelix-button framelix-button-primary" onclick=\'FramelixModal.callPhpMethod(' . JsonUtils::encode(
                JsCall::getCallUrl(__CLASS__, "editImageData", ['blockId' => $this->block->id])
            ) . ', FramelixForm.getById("' . $form->id . '").getValues(), {maxWidth:900})\'>' . Lang::get(
                '__pagemyself_component_slideshow_editimagedata__'
            ) . '</button>';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'thumbnails';
        $field->label = '__pagemyself_component_slideshow_' . strtolower($field->name) . '__';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'random';
        $field->label = '__pagemyself_component_slideshow_' . strtolower($field->name) . '__';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'showTitle';
        $field->label = '__pagemyself_component_slideshow_' . strtolower($field->name) . '__';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'showDescription';
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