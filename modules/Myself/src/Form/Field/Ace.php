<?php

namespace Framelix\Myself\Form\Field;

use Framelix\Framelix\Form\Field;

/**
 * Ace editor
 */
class Ace extends Field
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = "100%";

    /**
     * Editor mode
     * Supported is css|html|javascript
     * @var string
     */
    public string $mode = '';

    /**
     * Initial hidden
     * @var bool
     */
    public bool $initialHidden = false;

}