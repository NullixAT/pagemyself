<?php

namespace Framelix\PageMyself\PageBlock;

/**
 * A simple text block
 */
class Text extends Base
{
    /**
     * Get default settings for this block
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [
            'text' => ['text' => 'Your text here']
        ];
    }

    /**
     * Show content for this block
     * @return void
     */
    public function show(): void
    {
        echo '<div data-id="text">' . ($this->block->settings['text']['text'] ?? '') . '</div>';
    }
}