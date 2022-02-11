<?php

namespace Framelix\Myself\BlockLayout;

use JsonSerializable;

/**
 * BlockLayoutRow
 */
class BlockLayoutRow implements JsonSerializable
{

    /**
     * Columns
     * @var BlockLayoutColumn[]
     */
    public array $columns = [];

    /**
     * Row settings
     * @var BlockLayoutRowSettings
     */
    public BlockLayoutRowSettings $settings;

    /**
     * Internal row id
     * @var string|null
     */
    public ?string $rowId = null;

    /**
     * Create an instance from given data
     * @param array|null $data
     * @param string|null $rowId
     * @return self
     */
    public static function create(?array $data, ?string $rowId = null): self
    {
        $instance = new self();
        $instance->rowId = $rowId;
        if (isset($data['columns'])) {
            foreach ($data['columns'] as $key => $rowData) {
                $instance->columns[$key] = BlockLayoutColumn::create($rowData, $key);
            }
        }
        $instance->settings = BlockLayoutRowSettings::create($data['settings'] ?? null);
        return $instance;
    }

    /**
     * Json serialize
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['settings' => $this->settings, 'columns' => $this->columns];
    }

}