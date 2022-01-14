<?php

namespace Framelix\ImageGallery\PageBlocks;

use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Storable\Storable;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\MediaFile;

use function htmlentities;
use function str_starts_with;

/**
 * ImageGallery
 */
class ImageGallery extends BlockBase
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
        if (($this->pageBlock->pageBlockSettings['files'] ?? '') === 'demo') {
            $storables = [MediaFile::getByIdOrDemo('demo-image')];
        } else {
            $storables = Storable::getByIds($this->pageBlock->pageBlockSettings['files'] ?? []);
        }
        $files = MediaFile::getFlatListOfFilesRecursive($storables);
        $maxWidth = $this->pageBlock->pageBlockSettings['maxWidth'] ?? 200;
        foreach ($files as $file) {
            $imageData = $file->getImageData();
            if (!$imageData) {
                continue;
            }
            $useImage = null;
            $widthReached = false;
            $largestThumb = null;
            foreach ($imageData['sizes'] as $key => $row) {
                if (str_starts_with($key, "thumb-") && $row['thumbSize'] >= MediaFile::THUMBNAIL_SIZE_LARGE) {
                    $largestThumb = $row['url'];
                }
                if ($row['dimensions']['w'] > $maxWidth) {
                    if (!$widthReached) {
                        $useImage = $row;
                        $widthReached = true;
                    }
                    continue;
                }
                $useImage = $row;
            }
            $width = min($useImage['dimensions']['w'], $maxWidth);
            $ratio = 1 / $useImage['dimensions']['w'] * $width;
            $height = (int)($useImage['dimensions']['h'] * $ratio);
            if (!$largestThumb) {
                $largestThumb = $imageData['sizes']['original']['url'];
            }
            ?>
            <div class="imagegallery-pageblocks-imagegallery-image"
                 data-title="<?= htmlentities($imageData['title']) ?>"
                 data-image="<?= $useImage['url'] ?>"
                 data-large="<?= $largestThumb ?>"
                 data-maxwidth="<?= $maxWidth ?>"
                 style="width:<?= $width ?>px; height: <?= $height ?>px">
            </div>
            <?php
        }
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new MediaBrowser();
        $field->name = 'files';
        $field->multiple = true;
        $field->unfoldSelectedFolders = true;
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Number();
        $field->name = 'maxWidth';
        $field->required = true;
        $field->min = 10;
        $field->max = 10000;
        $field->defaultValue = 200;
        $form->addField($field);
    }
}