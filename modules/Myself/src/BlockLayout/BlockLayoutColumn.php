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
     * Internal column id
     * @var string|null
     */
    public ?string $columnId = null;

    /**
     * The assigned page block id
     * @var int
     */
    public int $pageBlockId = 0;

    /**
     * Create an instance from given data
     * @param array|null $data
     * @param string|null $columnId
     * @return self
     */
    public static function create(?array $data, ?string $columnId = null): self
    {
        $instance = new self();
        $instance->columnId = $columnId;
        $instance->settings = BlockLayoutColumnSettings::create($data['settings'] ?? null);
        $instance->pageBlockId = $data['pageBlockId'] ?? 0;
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