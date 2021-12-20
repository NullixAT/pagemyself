<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;

use function array_key_exists;

/**
 * Theme
 * @property string $module
 * @property string $name
 * @property mixed|null $settings
 */
class Theme extends StorableExtended
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
        $selfStorableSchema->addIndex('name', 'unique', ['module', 'name']);
    }

    /**
     * Get page blocks
     * @return PageBlock[]
     */
    public function getPageBlocks(): array
    {
        $cacheKey = "blocks";
        if (array_key_exists($cacheKey, $this->pageBlocks)) {
            return $this->pageBlocks[$cacheKey];
        }
        $this->pageBlocks[$cacheKey] = PageBlock::getByCondition(
            'theme = {0}',
            [$this]
        );
        return $this->pageBlocks[$cacheKey];
    }
}