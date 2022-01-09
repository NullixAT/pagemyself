<?php

namespace Framelix\Myself\BlockLayout;

use Framelix\Framelix\Db\StorablePropertyInterface;
use Framelix\Framelix\Db\StorableSchemaProperty;
use Framelix\Framelix\Utils\JsonUtils;

/**
 * BlockLayout
 */
class BlockLayout implements StorablePropertyInterface
{
    /**
     * Rows
     * @var BlockLayoutRow[]
     */
    public array $rows = [];

    /**
     * Setup the property database schema definition to this storable property itself
     * This defines how the column will be created in the database
     * @param StorableSchemaProperty $storableSchemaProperty
     */
    public static function setupSelfStorableSchemaProperty(StorableSchemaProperty $storableSchemaProperty): void
    {
        $storableSchemaProperty->databaseType = "longtext";
    }

    /**
     * Create an instance from the original database value
     * @param mixed $dbValue
     * @return self|null
     */
    public static function createFromDbValue(mixed $dbValue): ?self
    {
        return self::create($dbValue ? JsonUtils::decode($dbValue) : null);
    }

    /**
     * Create an instance from a submitted form value
     * @param mixed $formValue
     * @return self|null
     */
    public static function createFromFormValue(mixed $formValue): ?self
    {
        return self::create($formValue);
    }

    /**
     * Create an instance from given data
     * @param array|null $data
     * @return self|null
     */
    public static function create(?array $data): ?self
    {
        $instance = new self();
        if (isset($data['rows'])) {
            foreach ($data['rows'] as $key => $rowData) {
                $instance->rows[$key] = BlockLayoutRow::create($rowData);
            }
        }
        return $instance;
    }

    /**
     * To string
     * @return string
     */
    public function __toString(): string
    {
        return "BlockLayout";
    }


    /**
     * Get the database value that is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the property
     * @return string|null
     */
    public function getDbValue(): ?string
    {
        return $this->rows ? JsonUtils::encode($this->jsonSerialize()) : null;
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return (string)$this;
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return (string)$this;
    }

    /**
     * Get a value that can be used in sort functions
     * @return int
     */
    public function getSortableValue(): int
    {
        return 0;
    }

    /**
     * Get row at position
     * @param int $rowId
     * @return BlockLayoutRow|null
     */
    public function getRow(int $rowId): ?BlockLayoutRow
    {
        return $this->rows[$rowId] ?? null;
    }

    /**
     * Get column at position
     * @param int $rowId
     * @param int $columnId
     * @return BlockLayoutColumn|null
     */
    public function getColumn(int $rowId, int $columnId): ?BlockLayoutColumn
    {
        $row = $this->getRow($rowId);
        return $row->columns[$columnId] ?? null;
    }

    /**
     * Json serialize
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['rows' => $this->rows];
    }

}