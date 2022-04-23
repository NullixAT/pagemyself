<?php

namespace Framelix\PageMyself\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\PageMyself\Storable\MediaFile;

use function is_array;

/**
 * MediaBrowser
 */
class MediaBrowser extends Field
{
    /**
     * Allowed file extensions
     * @var string[]|null
     */
    public ?array $allowedExtensions = null;

    /**
     * Allow multiple select
     * @var bool
     */
    public bool $multiple = false;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        $disabled = $jsCall->parameters['disabled'] ?? (bool)(Request::getGet('disabled'));
        $multiple = $jsCall->parameters['multiple'] ?? (bool)(Request::getGet('multiple'));
        $allowedExtensions = $jsCall->parameters['allowedExtensions'] ?? Request::getGet('allowedExtensions');
        if (isset($_FILES['file'])) {
            ini_set("memory_limit", "2G");
            $uploadedFiles = UploadedFile::createFromSubmitData('file');
            try {
                foreach ($uploadedFiles as $uploadedFile) {
                    if (!in_array($uploadedFile->getExtension(), $allowedExtensions)) {
                        continue;
                    }
                    $replaceFile = MediaFile::getById(Request::getPost('replaceId'));
                    if ($replaceFile) {
                        $replaceFile->store($uploadedFile);
                    } else {
                        $mediaFile = new MediaFile();
                        $mediaFile->store($uploadedFile);
                    }
                    $jsCall->result = true;
                    return;
                }
            } catch (\Throwable $e) {
                $jsCall->result = $e->getMessage();
                return;
            }
            $jsCall->result = false;
            return;
        }

        $selectedValues = $jsCall->parameters['selectedValues'] ?? null;
        if (!is_array($selectedValues)) {
            $selectedValues = [$selectedValues];
        }
        $selectedValues = array_combine($selectedValues, $selectedValues);
        foreach ($selectedValues as $value) {
            $selectedValues[(int)$value] = (int)$value;
        }
        switch ($jsCall->parameters['action']) {
            case 'deleteFile':
                $mediaFile = MediaFile::getById($jsCall->parameters['id'] ?? null);
                $mediaFile?->delete();
                break;
            case 'browser':
                echo '<div class="mediabrowser">';
                if (!$disabled) {
                    $arr = [];
                    if (is_array($allowedExtensions)) {
                        foreach ($allowedExtensions as $allowedExtension) {
                            $arr[] = "." . $allowedExtension;
                        }
                    }
                    $field = new Field\File();
                    $field->name = "newFile";
                    $field->multiple = $multiple;
                    $field->allowedFileTypes = implode(",", $arr);
                    $field->show();
                }

                $files = MediaFile::getByCondition(sort: "+filename");
                foreach ($files as $file) {
                    ?>
                    <div class="mediabrowser-file" data-id="<?= $file ?>"
                         data-extension="<?= HtmlUtils::escape($file->extension) ?>"
                         data-url="<?= $file->getUrl()->removeParameter('t') ?>">
                        <span class="mediabrowser-file-checkbox"><input
                                    type="checkbox" <?= isset($selectedValues[$file->id]) ? 'checked' : '' ?>/></span>
                        <?php
                        if ($file->isImageFile()) {
                            ?>
                            <span class="mediabrowser-file-preview"
                                  style="background-image:url(<?= $file->getUrl(100) ?>)"></span>
                            <?php
                        }
                        ?>
                        <span class="mediabrowser-file-filename"><a href="<?= $file->getUrl() ?>"
                                                                    target="_blank"><?= HtmlUtils::escape(
                                    $file->filename
                                ) ?></a></span>
                        <?php
                        if (!$disabled) {
                            ?>
                            <span class="mediabrowser-file-actions">
                                <button class="framelix-button replace-file" data-icon-left="autorenew"
                                        title="__pagemyself_mediabrowser_replace__"></button>
                            </span>
                            <span class="mediabrowser-file-actions">
                                <button class="framelix-button delete-file" data-icon-left="delete"
                                        title="__framelix_deleteentry__"></button>
                            </span>
                            <?php
                        } ?>
                    </div>
                    <?php
                }
                echo '</div>';
                break;
        }
    }

    /**
     * Set allowing only images
     * @return void
     */
    public function setOnlyImages(): void
    {
        $this->allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'webp'];
    }

    /**
     * Set allowing only videos
     * @return void
     */
    public function setOnlyVideos(): void
    {
        $this->allowedExtensions = ['mp4', 'webm'];
    }

    /**
     * Get converted submitted value
     * @return string|array|int|null
     */
    public function getDefaultConvertedSubmittedValue(): string|array|int|null
    {
        $value = $this->getSubmittedValue();
        if (is_array($value)) {
            foreach ($value as $key => $v) {
                $value[$key] = (int)$v;
            }
            return $value;
        }
        if ($value) {
            return (int)$value;
        }
        return null;
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['properties']['apiUrl'] = JsCall::getCallUrl(
            __CLASS__,
            'api',
            [
                'allowedExtensions' => $this->allowedExtensions,
                'multiple' => $this->multiple,
                'disabled' => $this->disabled
            ]
        );
        return $data;
    }


}