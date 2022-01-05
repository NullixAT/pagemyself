<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;

use function array_key_exists;

/**
 * ThemeSettings
 * @property string $themeClass
 * @property mixed|null $settings
 */
class ThemeSettings extends StorableExtended
{
    /**
     * Cached page blocks
     * @var array
     */
    private array $pageBlocks = [];

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        $selfStorableSchema->addIndex('themeClass', 'unique');
    }

    /**
     * Get page blocks for this theme
     * @return PageBlock[]
     */
    public function getPageBlocks(): array
    {
        $cacheKey = "blocks";
        if (array_key_exists($cacheKey, $this->pageBlocks)) {
            return $this->pageBlocks[$cacheKey];
        }
        $this->pageBlocks[$cacheKey] = PageBlock::getByCondition(
            'themeClass = {0}',
            [$this->themeClass]
        );
        return $this->pageBlocks[$cacheKey];
    }
}