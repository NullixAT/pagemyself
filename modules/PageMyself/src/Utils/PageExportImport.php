<?php

namespace Framelix\PageMyself\Utils;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\PageMyself\Form\Field\MediaBrowser;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\MediaFile;
use Framelix\PageMyself\Storable\Page;
use Framelix\PageMyself\Storable\WebsiteSettings;

/**
 * Import export pages and blocks as json
 */
class PageExportImport
{
    /**
     * Export as json string
     * @param Page $page
     * @return string
     */
    public static function exportAsJson(Page $page): string
    {
        $blocks = $page->getComponentBlocks();
        $themeInstance = $page->getThemeInstance();
        $form = new Form();
        $themeInstance->addThemeSettingFields($form);
        $jsonData = ['blocks' => [], 'page' => ["theme" => $page->theme, "themeSettings" => []]];
        foreach ($form->fields as $field) {
            $jsonData['themeSettings'][$field->name] = WebsiteSettings::get(
                'theme_' . $themeInstance->themeId . "_" . $field->name
            );
        }
        foreach ($blocks as $block) {
            $form = new Form();
            $form->id = "blockSettings";
            $componentInstance = $block->getComponentInstance();
            $componentInstance->addSettingFields($form);
            $themeInstance->addComponentSettingFields($form, $componentInstance);
            $settings = $block->settings;
            foreach ($form->fields as $field) {
                $value = $settings[$field->name] ?? null;
                if (!$value) {
                    continue;
                }
                if ($field instanceof MediaBrowser) {
                    $files = MediaFile::getFlatList($value);
                    foreach ($files as $key => $file) {
                        $type = "other";
                        if ($file->isImage()) {
                            $type = "image";
                        } elseif ($file->isVideo()) {
                            $type = "video";
                        }
                        $files[$key] = "DEMO|" . $type;
                    }
                    if (!$files) {
                        $value = null;
                    } elseif (!$field->multiple) {
                        $value = reset($files);
                    }
                    $settings[$field->name] = $value;
                }
            }
            $jsonData['blocks'][] = [
                'blockClass' => $block->blockClass,
                'placement' => $block->placement,
                'sort' => $block->sort,
                'settings' => $settings
            ];
        }
        return JsonUtils::encode($jsonData, true);
    }

    /**
     * Import from a json string into given page
     * @param Page $page
     * @param string $jsonStr
     */
    public static function importFromJson(Page $page, string $jsonStr): void
    {
        $jsonData = JsonUtils::decode($jsonStr);
        $page->theme = $jsonData['page']['theme'];
        $page->store();
        $themeInstance = $page->getThemeInstance();
        $form = new Form();
        $themeInstance->addThemeSettingFields($form);
        $blocks = $page->getComponentBlocks();
        foreach ($blocks as $block) {
            $block->delete();
        }
        foreach ($jsonData['blocks'] as $blockData) {
            $block = new ComponentBlock();
            $block->page = $page;
            $block->blockClass = $blockData['blockClass'];
            $block->placement = $blockData['placement'];
            $block->sort = $blockData['sort'];
            $settings = $blockData['settings'];

            $form = new Form();
            $form->id = "blockSettings";
            $componentInstance = $block->getComponentInstance();
            $componentInstance->addSettingFields($form);
            $themeInstance->addComponentSettingFields($form, $componentInstance);

            $demoImage = __DIR__ . "/../../public/img/demo-image_2hmedia_unsplashed.jpg";
            $demoImageFile = MediaFile::getByConditionOne('filename = {0}', [basename($demoImage)]);
            if (!$demoImageFile) {
                $demoImageFile = new MediaFile();
                $demoImageFile->filename = basename($demoImage);
                $demoImageFile->store(file_get_contents($demoImage));
            }
            $demoFile = __DIR__ . "/../../public/img/demo-file.txt";
            $demoFileFile = MediaFile::getByConditionOne('filename = {0}', [basename($demoFile)]);
            if (!$demoFileFile) {
                $demoFileFile = new MediaFile();
                $demoFileFile->filename = basename($demoFile);
                $demoFileFile->store(file_get_contents($demoFile));
            }

            foreach ($form->fields as $field) {
                $value = $settings[$field->name] ?? null;
                if (!$value) {
                    continue;
                }
                if ($field instanceof MediaBrowser) {
                    $isArray = is_array($value);
                    if (!$isArray) {
                        $value = [$value];
                    }
                    foreach ($value as $key => $mediaType) {
                        $mediaType = substr($mediaType, 5);
                        if ($mediaType === "image") {
                            $file = $demoImageFile;
                        } else {
                            $file = $demoFileFile;
                        }
                        $value[$key] = $file->id;
                    }
                    if (!$isArray) {
                        $value = reset($value);
                    }
                    $settings[$field->name] = $value;
                }
            }

            // converting to json string to parse all hardcoded file links to uploaded files
            // and replace them with now existing demo media files
            $settingsStr = JsonUtils::encode($settings);
            preg_match_all("~uploads\\\\/\d+\\\\/([^\s\"'\\\\]+)~i", $settingsStr, $matchedLinks);
            if ($matchedLinks[0][0] ?? null) {
                foreach ($matchedLinks[0] as $key => $link) {
                    $filename = $matchedLinks[1][$key];
                    if (preg_match("~(jpeg|jpg|gif|png|webp)$~", $filename)) {
                        preg_match("~^t-([0-9]+)~", $filename, $thumbSize);
                        $settingsStr = str_replace(
                            $link,
                            substr(
                                JsonUtils::encode(
                                    $demoImageFile->getUrl(isset($thumbSize[1]) ? (int)$thumbSize[1] : null)
                                ),
                                1,
                                -1
                            ),
                            $settingsStr
                        );
                    }
                }
            }

            $block->settings = JsonUtils::decode($settingsStr);
            $block->store();
        }
    }
}