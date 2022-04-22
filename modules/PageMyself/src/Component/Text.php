<?php

namespace Framelix\PageMyself\Component;

/**
 * A simple text block
 */
class Text extends ComponentBase
{
    /**
     * Get default settings for this block
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [
            'text' => ['text' => '<p>Your text here</p>']
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