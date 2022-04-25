<?php

namespace Framelix\PageMyself\Component;

use Framelix\Framelix\Lang;

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
            'text' => ['text' => '<p>' . Lang::get('__pagemyself_component_text_default__') . '</p>']
        ];
    }

    /**
     * Show content for this block
     * @return void
     */
    public function show(): void
    {
        echo '<div class="pagemyself-component-text-text" data-id="text">' . ($this->block->settings['text']['text'] ?? '') . '</div>';
    }
}