<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Config;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Myself\PageBlocks\BlockBase;

use function class_exists;

/**
 * PageBlock
 * @property Page|null $page
 * @property string|null $themeClass
 * @property string|null $fixedPlacement
 * @property string|null $password
 * @property string $pageBlockClass
 * @property mixed|null $pageBlockSettings
 * @property bool $flagDraft
 */
class PageBlock extends StorableExtended
{

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        $selfStorableSchema->addIndex('themeClass', 'index');
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
        $module = ClassUtils::getModuleForClass($this->pageBlockClass);
        if (!(Config::$loadedModules[$module] ?? null)) {
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