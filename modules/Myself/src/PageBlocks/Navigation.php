<?php

namespace Framelix\Myself\PageBlocks;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\Storable\MediaFile;
use Framelix\Myself\Storable\Nav;
use Framelix\Myself\View\Index;

use function in_array;
use function rtrim;
use function str_repeat;

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
        echo '<nav>';
        $entries = $this->getNavChilds(null);
        $this->showNavEntries($entries);
        echo '</nav>';
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new MediaBrowser();
        $field->name = 'logo';
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Select();
        $field->name = 'allowedEntries';
        $field->multiple = true;
        $field->dropdown = false;
        $this->addNavSelectOptionRecursive(
            $field,
            $this->getNavChilds(null)
        );
        $form->addField($field);
    }

    /**
     * Add nav select options recursive to select field
     * @param Select $field
     * @param Nav[] $entries
     * @param int $level
     * @return void
     */
    private function addNavSelectOptionRecursive(Select $field, array $entries, int $level = 0): void
    {
        foreach ($entries as $entry) {
            $field->addOption($entry, str_repeat("&nbstp;", $level * 4) . $entry->getLabel());
            $childs = $this->getNavChilds($entry);
            $this->addNavSelectOptionRecursive($field, $childs, $level + 1);
        }
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
        $settings = $this->pageBlock->pageBlockSettings;
        if ($level === 0) {
            $logo = MediaFile::getById($settings['logo'] ?? null);
            if ($logo) {
                echo '<li class="' . $htmlClassBase . '-logo"><a href="' . Url::getApplicationUrl(
                    ) . '"><img src="' . $logo->getBiggestThumbUrl(
                        MediaFile::THUMBNAIL_SIZE_MEDIUM
                    ) . '" alt="Logo"></a></li>';
            }
        }
        foreach ($entries as $entry) {
            if (($settings['allowedEntries'] ?? null) && !in_array((string)$entry->id, $settings['allowedEntries'])) {
                continue;
            }
            $hasLink = ($entry->linkType === Nav::LINKTYPE_PAGE && $entry->page) || ($entry->linkType === Nav::LINKTYPE_CUSTOM && $entry->link);
            echo '<li>';
            if ($hasLink) {
                $url = $entry->linkType === Nav::LINKTYPE_PAGE
                    ? View::getUrl(Index::class, ['url' => $entry->page->url]) : $entry->link;
                $url = (string)$url;
                $active = rtrim($currentUrl, "/") === rtrim($url, "/");
                echo '<a href="' . $url . '" target="' . ($entry->target ?? '_self') . '" rel="nofollow" class="' . ($active ? 'myself-pageblocks-navigation-active-link' : '') . '">';
            } else {
                echo '<div class="' . $htmlClassBase . '-group">';
            }
            echo $entry->getLabel();
            if ($hasLink) {
                echo '</a>';
            } else {
                echo '</div>';
            }
            $childs = $this->getNavChilds($entry);
            if ($childs) {
                $this->showNavEntries($childs, $level + 1);
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Get nav childs for given parent
     * @param Nav|null $parent
     * @return Nav[]
     */
    private function getNavChilds(?Nav $parent): array
    {
        $condition = 'parent IS NULL';
        if ($parent) {
            $condition = "parent = " . $parent;
        }
        $condition .= " && (lang IS NULL || lang = {0})";
        if (!LayoutUtils::isEditAllowed()) {
            $condition .= " && flagDraft = false";
        }
        return Nav::getByCondition(
            $condition,
            [$this->pageBlock->page->lang ?? Lang::$lang],
            sort: ['+sort', '+title']
        );
    }
}