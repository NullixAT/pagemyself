<?php

namespace Framelix\Myself\BlockLayout;

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
        $instance->pageBlockId = $data['pageBlockId'];
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