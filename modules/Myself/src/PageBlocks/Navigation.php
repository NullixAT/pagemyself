<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\Nav;
use Framelix\Myself\View\Index;

/**
 * Navigation page block
 */
class Navigation extends BlockBase
{
    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $condition = 'parent IS NULL';
        if (!LayoutUtils::isEditAllowed()) {
            $condition .= " && flagDraft = false";
        }
        echo '<nav>';
        $entries = Nav::getByCondition($condition, sort: ['+sort', '+title']);
        $this->showNavEntries($entries);
        echo '</nav>';
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

        $field = new MediaBrowser();
        $field->name = 'pageBlockSettings[logo]';
        $field->setOnlyImages();
        $form->addField($field);

        return $forms;
    }


    /**
     * Show navigation entries list
     * @param Nav[] $entries
     * @param int $level
     * @return void
     */
    private function showNavEntries(array $entries, int $level = 0): void
    {
        $currentUrl = Url::create()->urlData['path'];
        $htmlClassBase = ClassUtils::getHtmlClass($this, "navlist");
        echo '<ul class="' . $htmlClassBase . '" data-level="' . $level . '">';
        if ($level === 0) {
            $settings = $this->pageBlock->pageBlockSettings;
            $logo = MediaFile::getById($settings['logo'] ?? null);
            if ($logo) {
                echo '<li class="' . $htmlClassBase . '-logo"><a href="' . Url::getApplicationUrl(
                    ) . '"><img src="' . $logo->getBiggestThumbUrl(
                        MediaFile::THUMBNAIL_SIZE_MEDIUM
                    ) . '" alt="Logo"></a></li>';
            }
        }
        foreach ($entries as $entry) {
            $allowed = true;
            if ($entry->pageTagsVisible) {
                $allowed = false;
                $pageTags = $this->pageBlock->page->tags;
                if ($pageTags) {
                    foreach ($pageTags as $pageTag) {
                        if (in_array($pageTag, $entry->pageTagsVisible)) {
                            $allowed = true;
                            break;
                        }
                    }
                }
            }
            if (!$allowed) {
                continue;
            }
            echo '<li>';
            if ($entry->page || $entry->link) {
                $url = ($entry->link ?: View::getUrl(Index::class, ['url' => $entry->page->url])->getRelativePath());
                $active = rtrim($currentUrl, "/") === rtrim($url, "/");
                echo '<a href="' . $url . '" target="' . ($entry->target ?? '_self') . '" rel="nofollow" class="' . ($active ? 'myself-pageblocks-navigation-active-link' : '') . '">';
            } else {
                echo '<div class="' . $htmlClassBase . '-group">';
            }
            echo $entry->getLabel();
            if ($entry->page || $entry->link) {
                echo '</a>';
            } else {
                echo '</div>';
            }
            $condition = 'parent = ' . $entry;
            if (!LayoutUtils::isEditAllowed()) {
                $condition .= " && flagDraft = false";
            }
            $childs = Nav::getByCondition($condition, sort: ['+sort', '+title']);
            if ($childs) {
                $this->showNavEntries($childs, $level + 1);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}