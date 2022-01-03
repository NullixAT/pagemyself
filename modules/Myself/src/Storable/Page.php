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
use function array_pop;
use function class_exists;
use function explode;

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
     * Cached theme
     * @var Theme|null
     */
    private ?Theme $theme = null;

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
     * @return Theme
     */
    public function getTheme(): Theme
    {
        if ($this->theme !== null) {
            return $this->theme;
        }
        $themeName = 'Hello';
        $themeModule = "Myself";
        if ($this->themeClass && class_exists($this->themeClass)) {
            $exp = explode("\\", $this->themeClass);
            $themeName = array_pop($exp);
            $themeModule = $exp[1];
        }
        $theme = Theme::getByConditionOne('module = {0} && name = {1}', [$themeModule, $themeName]);
        if (!$theme) {
            $theme = new Theme();
            $theme->module = $themeModule;
            $theme->name = $themeName;
            $theme->store();
        }
        $this->theme = $theme;
        return $this->theme;
    }

    /**
     * Get theme class name
     * @return string
     */
    public function getThemeClassName(): string
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
        $className = $this->getThemeClassName();
        return new $className($this->getTheme(), $this);
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