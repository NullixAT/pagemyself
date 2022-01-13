<?php

namespace Framelix\Myself\BlockLayout;

use JsonSerializable;

use function is_array;
use function property_exists;

/**
 * BlockLayoutRowSettings
 */
class BlockLayoutRowSettings implements JsonSerializable
{

    /**
     * Gap between columns in px
     * @var int|null
     */
    public ?int $gap = null;

    /**
     * Max width of row
     * @var int|null
     */
    public ?int $maxWidth = null;

    /**
     * Column alignment
     * @var string|null
     */
    public ?string $alignment = null;

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
     * Background position
     * @var string|null
     */
    public ?string $backgroundPosition = null;

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