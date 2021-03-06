<?php

namespace Framelix\PageMyself\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\JsCallUnsigned;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\PageMyself\Storable\MediaFile;
use Framelix\PageMyself\Storable\MediaFolder;
use Throwable;

use function array_reverse;
use function explode;
use function implode;
use function is_array;
use function is_string;

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
     * @param JsCallUnsigned|JsCall $jsCall
     */
    public static function onJsCall(JsCallUnsigned|JsCall $jsCall): void
    {
        if (!User::get()) {
            return;
        }
        $disabled = $jsCall->parameters['disabled'] ?? (bool)(Request::getGet('disabled'));
        $multiple = $jsCall->parameters['multiple'] ?? (bool)(Request::getGet('multiple'));
        $allowedExtensions = $jsCall->parameters['allowedExtensions'] ?? Request::getGet(
                'allowedExtensions'
            ) ?? Request::getPost('allowedExtensions');
        if (is_string($allowedExtensions)) {
            $allowedExtensions = explode(",", $allowedExtensions);
        }
        if (isset($_FILES['file'])) {
            ini_set("memory_limit", "2G");
            $uploadedFiles = UploadedFile::createFromSubmitData('file');
            $currentFolder = MediaFolder::getById(Request::getPost('currentFolder'));
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
                        $mediaFile->mediaFolder = $currentFolder;
                        $mediaFile->store($uploadedFile);
                    }
                    $jsCall->result = true;
                    return;
                }
            } catch (Throwable $e) {
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
        $currentFolder = MediaFolder::getById($jsCall->parameters['currentFolder'] ?? null);
        switch ($jsCall->parameters['action']) {
            case 'createFolder':
                $newFolder = new MediaFolder();
                $newFolder->parent = $currentFolder;
                $newFolder->name = $jsCall->parameters['name'];
                $newFolder->store();
                $jsCall->result = $newFolder->id;
                break;
            case 'deleteFile':
                $mediaFile = MediaFile::getById($jsCall->parameters['id'] ?? null);
                $mediaFile?->delete();
                break;
            case 'deleteFolder':
                $mediaFolder = MediaFolder::getById($jsCall->parameters['id'] ?? null);
                $mediaFolder?->delete();
                break;
            case 'renameEntry':
                $media = MediaFolder::getById($jsCall->parameters['id'] ?? null);
                if ($media) {
                    $media->name = $jsCall->parameters['name'];
                    $media->store();
                }
                $media = MediaFile::getById($jsCall->parameters['id'] ?? null);
                if ($media) {
                    $media->filename = $jsCall->parameters['name'];
                    $media->store();
                }
                break;
            case 'browser':
                echo '<div class="mediabrowser">';
                $name = [];
                if ($currentFolder) {
                    $name[] = '<span>' . $currentFolder->name . '</span>';
                    $parent = $currentFolder;
                    while ($parent = $parent->parent) {
                        $name[] = '<a href="#" data-folder-id="' . $parent . '">' . $parent->name . '</a>';
                    }
                    $name[] = '<a href="#" data-folder-id="">' . Lang::get(
                            '__pagemyself_mediabrowser_rootfolder__'
                        ) . '</a>';
                    $name = array_reverse($name);
                } else {
                    $name[] = Lang::get('__pagemyself_mediabrowser_rootfolder__');
                }

                ?>
                <h2 style="display: flex; align-items: center; gap:10px; border-bottom:1px solid var(--color-border-subtle)">
                    <span class="material-icons" style="color:var(--color-warning-text)">folder</span>
                    <?= implode(" / ", $name) ?>
                </h2>
                <div class="mediabrowser-action-bar">
                    <?php
                    if (!$disabled) {
                        $arr = [];
                        if (is_array($allowedExtensions)) {
                            foreach ($allowedExtensions as $allowedExtension) {
                                $arr[] = "." . $allowedExtension;
                            }
                        }
                        $field = new Field\File();
                        $field->name = "newFile";
                        $field->multiple = true;
                        $field->allowedFileTypes = implode(",", $arr);
                        $field->maxWidth = null;
                        $field->show();
                        ?>
                        <button class="framelix-button framelix-button-warning create-folder"
                                data-icon-left="create_new_folder">
                            <?= Lang::get('__pagemyself_mediabrowser_createfolder__') ?>
                        </button>
                        <?php
                    }
                    ?>
                </div>
                <?php

                $folders = MediaFolder::getByCondition(
                    $currentFolder ? 'parent = {0}' : 'parent IS NULL',
                    [$currentFolder],
                    "+name"
                );
                foreach ($folders as $folder) {
                    ?>
                    <div class="mediabrowser-folder" data-id="<?= $folder ?>">
                        <?php
                        if ($multiple) {
                            ?>
                            <span class="mediabrowser-file-checkbox"><input
                                        type="checkbox" <?= isset($selectedValues[$folder->id]) ? 'checked' : '' ?>/></span>
                            <?php
                        }
                        ?>
                        <span class="mediabrowser-file-icon"><span class="material-icons"
                                                                   style="color:var(--color-warning-text)">folder</span></span>
                        <span class="mediabrowser-file-filename"><?= $folder->name ?></span>
                        <?php
                        if (!$disabled) {
                            ?>
                            <span class="mediabrowser-file-actions">
                                <button class="framelix-button rename-entry" data-id="<?= $folder ?>"
                                        data-default="<?= HtmlUtils::escape($folder->name) ?>" data-icon-left="edit"
                                        title="__pagemyself_mediabrowser_rename__"></button>
                            </span>
                            <span class="mediabrowser-file-actions">
                                <button class="framelix-button delete-folder" data-icon-left="delete"
                                        title="__pagemyself_mediabrowser_deletefolder__"></button>
                            </span>
                            <?php
                        } ?>
                    </div>
                    <?php
                }

                $files = MediaFile::getByCondition(
                    $currentFolder ? 'mediaFolder = {0}' : 'mediaFolder IS NULL',
                    [$currentFolder],
                    "+filename"
                );
                foreach ($files as $file) {
                    if ($allowedExtensions && !in_array($file->extension, $allowedExtensions)) {
                        continue;
                    }
                    ?>
                    <div class="mediabrowser-file" data-id="<?= $file ?>"
                         data-extension="<?= HtmlUtils::escape($file->extension) ?>"
                         data-url="<?= $file->getUrl()->removeParameter('t') ?>">
                        <span class="mediabrowser-file-checkbox"><input
                                    type="checkbox" <?= isset($selectedValues[$file->id]) ? 'checked' : '' ?>/></span>
                        <?php
                        if ($file->isImage()) {
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
                                <button class="framelix-button rename-entry" data-id="<?= $file ?>"
                                        data-default="<?= HtmlUtils::escape($file->filename) ?>" data-icon-left="edit"
                                        title="__pagemyself_mediabrowser_rename__"></button>
                            </span>
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
        $this->allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'webp', 'svg'];
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
            return Storable::getByIds($value);
        }
        if ($value) {
            return Storable::getById($value);
        }
        return null;
    }
}