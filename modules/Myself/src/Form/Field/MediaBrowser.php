<?php

namespace Framelix\Myself\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Url;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\MediaFileFolder;
use Throwable;

use function htmlentities;
use function implode;
use function in_array;
use function ini_set;
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
     * If true, when a user select a folder, all files inside will be unfolded and added to the selection list
     * @var bool
     */
    public bool $unfoldSelectedFolders = false;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        $allowedExtensions = Request::getGet('allowedExtensions');
        $selectedIds = $jsCall->parameters['selectedIds'] ?? null;
        if (is_string($selectedIds)) {
            $selectedIds = [$selectedIds];
        }
        $mediaFile = MediaFile::getById(Request::getGet('file'));
        $mediaFolder = MediaFileFolder::getById(Request::getGet('folder'));
        switch ($jsCall->action) {
            case 'rename':
                if ($mediaFolder) {
                    $mediaFolder->name = $jsCall->parameters['newName'] ?? null;
                    $mediaFolder->store();
                    $jsCall->result = true;
                }
                if ($mediaFile) {
                    $mediaFile->title = $jsCall->parameters['newName'] ?? null;
                    $mediaFile->store();
                    $jsCall->result = true;
                }
                break;
            case 'delete-folder':
                $mediaFolder?->delete();
                $jsCall->result = true;
                break;
            case 'delete-file':
                $mediaFile?->delete();
                $jsCall->result = true;
                break;
            case 'create-folder':
                $folder = new MediaFileFolder();
                $folder->name = $jsCall->parameters['folderName'] ?? null;
                $folder->parent = $mediaFolder;
                $folder->store();
                break;
            case 'list':
                if ($jsCall->parameters['unfoldFolder'] ?? null) {
                    $unfoldFolder = MediaFileFolder::getById($jsCall->parameters['unfoldFolder']);
                    if ($unfoldFolder) {
                        $files = $unfoldFolder->getAllChildFiles();
                        foreach ($files as $file) {
                            self::showEntryForFile($file, true);
                        }
                    }
                    return;
                }
                if (isset($_FILES['file'])) {
                    ini_set("memory_limit", "2G");
                    $uploadedFiles = UploadedFile::createFromSubmitData('file');
                    try {
                        foreach ($uploadedFiles as $uploadedFile) {
                            if ($allowedExtensions && !in_array($uploadedFile->getExtension(), $allowedExtensions)) {
                                continue;
                            }
                            $replaceFile = MediaFile::getById(Request::getPost('replaceId'));
                            if ($replaceFile) {
                                $replaceFile->store($uploadedFile);
                            } else {
                                $mediaFile = new MediaFile();
                                $mediaFile->mediaFileFolder = $mediaFolder;
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
                $createFolderUrl = JsCall::getCallUrl(
                    __CLASS__,
                    'create-folder',
                    ['folder' => $mediaFolder]
                );
                ?>
                <label class="myself-media-browser-replace-file hidden">
                    <input type="file"
                           accept="<?= $allowedExtensions ? "." . implode(",.", $allowedExtensions) : '*/*' ?>"
                           style="display: none" multiple>
                </label>
                <?php
                $selectedStorables = null;
                echo '<h3>' . Lang::get('__myself_mediabrowser_selected_entries__') . '<br/><small>' . Lang::get(
                        '__myself_mediabrowser_selected_entries_info__'
                    ) . '</small></h3>';
                echo '<div class="myself-media-browser-entries" data-type="selected">';
                if ($selectedIds) {
                    $selectedStorables = Storable::getByIds($selectedIds);
                    foreach ($selectedStorables as $storable) {
                        if ($storable instanceof MediaFileFolder) {
                            self::showEntryForFolder($storable, true);
                        }
                        if ($storable instanceof MediaFile) {
                            self::showEntryForFile($storable, true);
                        }
                    }
                }
                echo '</div>';
                echo '<h3>' . Lang::get('__myself_mediabrowser_available_entries__') . '</h3>';
                echo '<div class="myself-media-browser-entries" data-type="unselected">';
                if ($mediaFolder) {
                    self::showEntryForFolder($mediaFolder, false, true);
                }
                ?>
                <label class="myself-media-browser-entry myself-media-browser-entry-upload"
                       tabindex="0">
                    <span class="myself-media-browser-entry-icon"><span
                                class="material-icons">upload</span></span>
                    <span class="myself-media-browser-entry-label"><?= Lang::get(
                            '__myself_mediabrowser_upload__'
                        ) ?></span>
                    <input type="file"
                           accept="<?= $allowedExtensions ? "." . implode(",.", $allowedExtensions) : '*/*' ?>"
                           style="display: none" multiple>
                </label>
                <div class="myself-media-browser-entry myself-media-browser-entry-create-folder framelix-space-click"
                     tabindex="0" data-create-folder="<?= $createFolderUrl ?>">
                    <span class="myself-media-browser-entry-icon"><span
                                class="material-icons">create_new_folder</span></span>
                    <span class="myself-media-browser-entry-label"><?= Lang::get(
                            '__myself_mediabrowser_createfolder__'
                        ) ?></span>
                </div>
                <?php
                $folders = MediaFileFolder::getByCondition(
                    $mediaFolder ? 'parent = {0}' : 'parent IS NULL',
                    [$mediaFolder],
                    ["+name"]
                );
                foreach ($folders as $folder) {
                    if (isset($selectedStorables[$folder->id])) {
                        continue;
                    }
                    self::showEntryForFolder($folder, $selectedIds && in_array($folder->id, $selectedIds));
                }
                $files = MediaFile::getByCondition(
                    $mediaFolder ? 'mediaFileFolder = {0}' : 'mediaFileFolder IS NULL',
                    [$mediaFolder],
                    ["+filename"]
                );
                foreach ($files as $file) {
                    if (isset($selectedStorables[$file->id])) {
                        continue;
                    }
                    if ($allowedExtensions && !in_array($file->extension, $allowedExtensions)) {
                        continue;
                    }
                    self::showEntryForFile($file, $selectedIds && in_array($file->id, $selectedIds));
                }
                echo '</div>';
                break;
        }
    }

    /**
     * Show entry for folder
     * @param MediaFileFolder $folder
     * @param bool $selected
     * @param bool $parentFolder
     * @return void
     */
    public static function showEntryForFolder(MediaFileFolder $folder, bool $selected, bool $parentFolder = false): void
    {
        $openFolderUrl = JsCall::getCallUrl(
            __CLASS__,
            'list',
            [
                'folder' => $parentFolder ? $folder->parent : $folder,
                'allowedExtensions' => Request::getGet('allowedExtensions')
            ]
        );
        $deleteUrl = JsCall::getCallUrl(
            __CLASS__,
            'delete-folder',
            ['folder' => $folder]
        );
        $renameUrl = JsCall::getCallUrl(
            __CLASS__,
            'rename',
            ['folder' => $folder]
        );
        ?>
        <div class="myself-media-browser-entry myself-media-browser-entry-folder"
             data-id="<?= $folder ?>"
             data-load-url="<?= $openFolderUrl ?>"
             tabindex="0">
            <div class="myself-media-browser-entry-icon myself-media-browser-entry-load-url"><span
                        class="material-icons"><?= $parentFolder ? 'snippet_folder' : 'folder' ?></span></div>
            <div class="myself-media-browser-entry-label">
                <?
                if ($parentFolder) {
                    echo Lang::get('__myself_mediabrowser_parent_folder__');
                } else {
                    echo htmlentities($folder->name);
                }
                ?>
            </div>
            <?
            if (!$parentFolder) {
                ?>
                <button class="framelix-button myself-media-browser-entry-options-icon"
                        data-icon-left="settings"></button>
                <div class="myself-media-browser-entry-options hidden">
                    <button class="framelix-button myself-media-browser-entry-rename"
                            data-rename-url="<?= $renameUrl ?>"
                            data-title="<?= htmlentities($folder->name) ?>"
                            data-icon-left="edit"><?= Lang::get(
                            '__myself_mediabrowser_rename__'
                        ) ?></button>
                    <button class="framelix-button framelix-button-error myself-media-browser-entry-delete"
                            data-icon-left="clear" data-delete-folder-url="<?= $deleteUrl ?>"><?= Lang::get(
                            '__myself_mediabrowser_delete_folder__'
                        ) ?></button>
                </div>
                <input class="myself-media-browser-entry-select" type="checkbox" <?= $selected ? 'checked' : '' ?>
                       title="__myself_mediabrowser_select_entry__">
                <?
            } ?>
        </div>
        <?php
    }

    /**
     * Show entry for file
     * @param MediaFile $file
     * @param bool $selected
     * @return void
     */
    public static function showEntryForFile(MediaFile $file, bool $selected): void
    {
        $deleteUrl = JsCall::getCallUrl(
            __CLASS__,
            'delete-file',
            ['file' => $file]
        );
        $renameUrl = JsCall::getCallUrl(
            __CLASS__,
            'rename',
            ['file' => $file]
        );
        $attributes = new HtmlAttributes();
        $attributes->addClass(
            'myself-media-browser-entry myself-media-browser-entry-selectable myself-media-browser-entry-file framelix-space-click'
        );
        $attributes->set('tabindex', 0);
        $attributes->set('data-id', $file);
        if ($file->getPath()) {
            $attributes->set('data-url', Url::getUrlToFile($file->getPath()));
        }
        ?>
        <div <?= $attributes ?>>
            <?
            if (!$file->getPath()) {
                echo '<div class="myself-media-browser-entry-icon" title="__myself_file_not_exist__"><span class="material-icons">do_not_disturb</span></div>';
            } elseif ($file->isImageFile() && $file->getSmallestThumbPath()) {
                echo '<div class="myself-media-browser-entry-icon myself-lazy-load-parent-anchor">' . $file->getLazyLoadContainer(
                    ) . '</div>';
            } elseif ($file->isVideoFile()) {
                echo '<div class="myself-media-browser-entry-icon"><span class="material-icons">ondemand_video</span></div>';
            } else {
                echo '<div class="myself-media-browser-entry-icon"><span class="material-icons">file_open</span></div>';
            }
            ?>
            <div class="myself-media-browser-entry-label">
                <?
                echo htmlentities($file->title ?? $file->filename ?? '');
                ?>
            </div>
            <button class="framelix-button myself-media-browser-entry-options-icon" data-icon-left="settings"></button>
            <div class="myself-media-browser-entry-options hidden">
                <button class="framelix-button myself-media-browser-entry-rename"
                        data-rename-url="<?= $renameUrl ?>"
                        data-title="<?= htmlentities($file->title ?? $file->filename ?? '') ?>"
                        data-icon-left="edit"><?= Lang::get(
                        '__myself_mediabrowser_rename__'
                    ) ?></button>
                <button class="framelix-button framelix-button-primary myself-media-browser-entry-replace"
                        data-replace-id="<?= $file ?>" data-icon-left="upgrade"><?= Lang::get(
                        '__myself_mediabrowser_replace__'
                    ) ?></button>
                <button class="framelix-button framelix-button-error myself-media-browser-entry-delete"
                        data-icon-left="clear" data-delete-file-url="<?= $deleteUrl ?>"><?= Lang::get(
                        '__myself_mediabrowser_delete_file__'
                    ) ?></button>
            </div>
            <input type="checkbox" class="myself-media-browser-entry-select"
                   title="__myself_mediabrowser_select_entry__" <?= $selected ? 'checked' : '' ?>>
            <div class="myself-media-browser-entry-preview hidden"></div>
        </div>
        <?php
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
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['properties']['signedGetBrowserUrl'] = JsCall::getCallUrl(
            __CLASS__,
            'list',
            ['allowedExtensions' => $this->allowedExtensions]
        );
        return $data;
    }


}