<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Framelix\PageMyself\View\Backend\Page\Index;

/**
 * Page
 * @property int $category
 * @property Page|null $parent
 * @property string $title
 * @property string|null $password
 * @property string|null $url
 * @property string|null $link
 * @property bool $flagDraft
 * @property bool $flagNav
 * @property mixed|null $layoutSettings
 * @property int|null $sort
 * @property string|null $navGroup
 */
class Page extends StorableExtended
{
    public const CATEGORY_PAGE = 1;
    public const CATEGORY_EXTERNAL = 2;

    /**
     * Categories
     * @var int[]
     */
    public static array $categories = [self::CATEGORY_PAGE, self::CATEGORY_EXTERNAL];

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
        parent::delete($force);
    }


}