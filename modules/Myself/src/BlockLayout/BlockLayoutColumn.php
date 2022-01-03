<?php

namespace Framelix\Myself\BlockLayout;

use Framelix\Myself\Storable\PageBlock;
use JsonSerializable;

/**
 * BlockLayout
 */
class BlockLayoutColumn implements JsonSerializable
{

    /**
     * Column settings
     * @var BlockLayoutColumnSettings
     */
    public BlockLayoutColumnSettings $settings;

    /**
     * The assigned page block id
     * @var int
     */
    public int $pageBlockId = 0;

    /**
     * Create an instance from given data
     * @param array|null $data
     * @return self
     */
    public static function create(?array $data): self
    {
        $instance = new self();
        $instance->settings = BlockLayoutColumnSettings::create($data['settings'] ?? null);
        // fetch and set id to valid if id still exist in database
        $instance->pageBlockId = PageBlock::getById($data['pageBlockId'] ?? null)->id ?? 0;
        return $instance;
    }

    /**
     * Json serialize
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['settings' => $this->settings, 'pageBlockId' => $this->pageBlockId];
    }
}