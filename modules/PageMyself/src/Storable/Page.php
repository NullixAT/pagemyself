<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\PageMyself\ThemeBase;
use Framelix\PageMyself\View\Backend\Page\Index;

use function class_exists;
use function file_exists;

/**
 * Page
 * @property string $title
 * @property string|null $url
 * @property string|null $password
 * @property string|null $theme
 * @property int|null $sort
 */
class Page extends StorableExtended
{
    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->addIndex('url', 'unique');
    }

    /**
     * Get the default page
     * @return Page
     */
    public static function getDefault(): Page
    {
        $storable = self::getByConditionOne('url = {0}', ['']);
        if (!$storable) {
            $storable = new self();
            $storable->title = 'Homepage';
            $storable->url = '';
            $storable->sort = 0;
            $storable->store();
        }
        return $storable;
    }

    /**
     * Get theme instance
     * @return ThemeBase
     */
    public function getThemeInstance(): ThemeBase
    {
        $theme = $this->theme ?? 'Hello';
        $classFile = __DIR__ . "/../../public/themes/$theme/Theme.php";
        if (!file_exists($classFile)) {
            $theme = 'Hello';
            $classFile = __DIR__ . "/../../public/themes/Hello/Theme.php";
        }
        require_once $classFile;
        $className = "Framelix\\PageMyself\\Themes\\$theme\\Theme";
        /** @var ThemeBase $instance */
        $instance = new $className();
        $instance->page = $this;
        return $instance;
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
     * Get public url
     * @return Url
     */
    public function getPublicUrl(): Url
    {
        return View::getUrl(\Framelix\PageMyself\View\Index::class, ["url" => $this->url]);
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
     * Get all page blocks to this page
     * @return ComponentBlock[]
     */
    public function getComponentBlocks(): array
    {
        $componentBlocks = ComponentBlock::getByCondition('page = {0}', [$this], ["+sort", "+id"]);
        // in case some classes have been removed
        foreach ($componentBlocks as $key => $componentBlock) {
            if (!class_exists($componentBlock->blockClass)) {
                unset($componentBlocks[$key]);
            }
        }
        return $componentBlocks;
    }

    /**
     * Store
     */
    public function store(): void
    {
        parent::store();
        $navEntry = NavEntry::getByConditionOne('page = {0}', [$this]);
        if (!$navEntry) {
            $lastSort = NavEntry::getByConditionOne(sort: "-sort")->sort ?? -1;
            $lastSort++;
            $navEntry = new NavEntry();
            $navEntry->page = $this;
            $navEntry->title = $this->title;
            $navEntry->flagShow = true;
            $navEntry->sort = $lastSort;
            $navEntry->store();
        }
    }

    /**
     * Delete
     * @param bool $force
     */
    public function delete(bool $force = false): void
    {
        self::deleteMultiple($this->getComponentBlocks());
        self::deleteMultiple(NavEntry::getByCondition('page = {0}', [$this]));
        parent::delete($force);
    }
}