<?php

namespace Framelix\Myself\BlockLayout;

use JsonSerializable;

/**
 * BlockLayoutColumnSettings
 */
class BlockLayoutColumnSettings implements JsonSerializable
{

    /**
     * Padding in px
     * @var int|null
     */
    public ?int $padding = null;

    /**
     * Min width of column
     * @var int|null
     */
    public ?int $minWidth = null;

    /**
     * Min height of column
     * @var int|null
     */
    public ?int $minHeight = null;

    /**
     * Vertical text alignment if minHeight is set
     * @var string|null
     */
    public ?string $textVerticalAlignment = null;

    /**
     * Text size in percent
     * @var int|null
     */
    public ?int $textSize = null;

    /**
     * Text color
     * @var string|null
     */
    public ?string $textColor = null;

    /**
     * Text alignment
     * @var string|null
     */
    public ?string $textAlignment = null;

    /**
     * Background color
     * @var string|null
     */
    public ?string $backgroundColor = null;

    /**
     * Background image media file id
     * @var int|null
     */
    public ?int $backgroundImage = null;

    /**
     * Background video media file id
     * @var int|null
     */
    public ?int $backgroundVideo = null;

    /**
     * Background size
     * @var string|null
     */
    public ?string $backgroundSize = null;

    /**
     * Grow
     * @var int
     */
    public int $grow = 1;

    /**
     * Create an instance from given data
     * @param array|null $data
     * @return self
     */
    public static function create(?array $data): self
    {
        $instance = new self();
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (property_exists($instance, $key)) {
                    if ($value === '') {
                        $value = null;
                    }
                    $instance->{$key} = $value;
                }
            }
        }
        return $instance;
    }

    /**
     * Json serialize
     * @return array
     */
    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}