<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Myself\PageBlocks\BlockBase;

use function class_exists;
use function is_array;

/**
 * PageBlock
 * @property Page|null $page
 * @property Theme|null $theme
 * @property string|null $fixedPlacement
 * @property string|null $password
 * @property string $pageBlockClass
 * @property mixed|null $pageBlockSettings
 * @property bool $flagDraft
 */
class PageBlock extends StorableExtended
{

    /**
     * Get all page blocks that not have a dedicated layout column
     * @return PageBlock[]
     */
    public static function getBlocksWithUnassignedLayoutColumn(): array
    {
        $config = WebsiteSettings::get('blockLayout');
        $pageBlockIdAssigned = [];
        if (is_array($config['rows'] ?? null)) {
            foreach ($config['rows'] as $rowId => $row) {
                $columns = $row['columns'] ?? null;
                if (is_array($columns)) {
                    foreach ($columns as $columnRow) {
                        if ($columnRow['pageBlockId'] ?? null) {
                            $pageBlockIdAssigned[$columnRow['pageBlockId']] = $columnRow['pageBlockId'];
                        }
                    }
                }
            }
        }
        return PageBlock::getByCondition($pageBlockIdAssigned ? 'id NOT IN {0}' : null, [$pageBlockIdAssigned]);
    }

    /**
     * Get layout block
     * @return BlockBase|null
     */
    public function getLayoutBlock(): ?BlockBase
    {
        if (!$this->pageBlockClass || !class_exists($this->pageBlockClass)) {
            return null;
        }
        $instance = new $this->pageBlockClass($this);
        if ($instance instanceof BlockBase) {
            return $instance;
        }
        return null;
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }
}