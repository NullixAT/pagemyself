<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Myself\PageBlocks\BlockBase;

use function class_exists;

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