<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\Myself\BlockLayout\BlockLayout;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\Themes\Hello;
use Framelix\Myself\Themes\ThemeBase;
use Framelix\Myself\View\Backend\Page\Index;

use function array_key_exists;
use function class_exists;

/**
 * Page
 * @property string|null $themeClass
 * @property string $title
 * @property string|null $password
 * @property string|null $url
 * @property mixed|null $settings
 * @property bool $flagDraft
 * @property string $lang
 * @property BlockLayout|null $blockLayout
 */
class Page extends StorableExtended
{
    /**
     * Cached page blocks
     * @var array
     */
    private array $pageBlocks = [];

    /**
     * Cached theme settings for getter
     * @var ThemeSettings|null
     */
    private ?ThemeSettings $themeSettings = null;

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['lang']->length = 5;
        $selfStorableSchema->addIndex('url', 'unique');
    }

    /**
     * Get block layout
     * Create one if not exist
     * @return BlockLayout
     */
    public function getBlockLayout(): BlockLayout
    {
        if (!$this->blockLayout) {
            $this->blockLayout = new BlockLayout();
        }
        return $this->blockLayout;
    }

    /**
     * Get page blocks
     * @param bool $filterDraft
     * @return PageBlock[]
     */
    public function getPageBlocks(bool $filterDraft = true): array
    {
        $cacheKey = (int)$filterDraft;
        if (array_key_exists($cacheKey, $this->pageBlocks)) {
            return $this->pageBlocks[$cacheKey];
        }
        $this->pageBlocks[$cacheKey] = PageBlock::getByCondition(
            'page = {0} ' . ($filterDraft && !LayoutUtils::isEditAllowed() ? ' && flagDraft = 0' : ''),
            [$this, User::get()]
        );
        return $this->pageBlocks[$cacheKey];
    }

    /**
     * Get theme to this page
     * @return ThemeSettings
     */
    public function getThemeSettings(): ThemeSettings
    {
        if ($this->themeSettings !== null) {
            return $this->themeSettings;
        }
        $themeSettings = ThemeSettings::getByConditionOne('themeClass = {0}', [$this->getThemeClass()]);
        if (!$themeSettings) {
            $themeSettings = new ThemeSettings();
            $themeSettings->themeClass = $this->getThemeClass();
            $themeSettings->store();
        }
        $this->themeSettings = $themeSettings;
        return $this->themeSettings;
    }

    /**
     * Get theme class for this page
     * Returns a default class if not set
     * @return string
     */
    public function getThemeClass(): string
    {
        $className = $this->themeClass ?? Hello::class;
        if (!class_exists($className)) {
            $className = Hello::class;
        }
        return $className;
    }

    /**
     * Get theme layout block
     * @return ThemeBase
     */
    public function getThemeBlock(): ThemeBase
    {
        $className = $this->getThemeClass();
        return new $className($this->getThemeSettings(), $this);
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }

    /**
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return View::getUrl(Index::class)->setParameter('id', $this)->setHash('tabs:edit');
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return $this->title;
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this->title;
    }

    /**
     * Delete from database
     * @param bool $force Force deletion even if isDeletable() is false
     */
    public function delete(bool $force = false): void
    {
        self::deleteMultiple($this->getPageBlocks(false));
        parent::delete($force);
    }


}