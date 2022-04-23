<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\PageMyself\Component\ComponentBase;

/**
 * Component block
 * @property Page $page
 * @property string $blockClass
 * @property string $placement
 * @property int $sort
 * @property mixed|null $settings
 */
class ComponentBlock extends StorableExtended
{
    /**
     * Get public url
     * @return Url
     */
    public function getPublicUrl(): Url
    {
        return $this->page->getPublicUrl()->setHash('block-' . $this);
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
     * Get component instance
     * @return ComponentBase
     */
    public function getComponentInstance(): ComponentBase
    {
        /** @var ComponentBase $instance */
        $instance = new $this->blockClass();
        $instance->block = $this;
        return $instance;
    }
}