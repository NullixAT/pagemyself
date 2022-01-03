<?php

namespace Framelix\Myself\BlockLayout;

/**
 * PredefinedBlockLayout
 */
class PredefinedBlockLayout
{
    /**
     * The label for the user interface
     * @var string|null
     */
    public ?string $label = null;

    /**
     * The description for the user interface
     * @var string|null
     */
    public ?string $description = null;

    /**
     * The filename to the image thumbnail for visual demo of this block layout
     * This file must exist inside public/themes/yourthemename folder
     * @var string|null
     */
    public ?string $thumbnailFilename = null;

    /**
     * Block layout
     * @var BlockLayout
     */
    public BlockLayout $blockLayout;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->blockLayout = new BlockLayout();
    }
}