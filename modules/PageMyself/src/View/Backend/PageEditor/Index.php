<?php

namespace Framelix\PageMyself\View\Backend\PageEditor;

use Framelix\Framelix\View\Backend\View;

/**
 * Index
 */
class Index extends View
{
    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "pageeditor");
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "pageeditor");
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show the page content
     */
    public function showContent(): void
    {
        ?>
        <div class="pageeditor-frame">
            <div class="pageeditor-frame-top"></div>
            <iframe src="<?= \Framelix\Framelix\View::getUrl(\Framelix\PageMyself\View\Index::class) ?>" width="100%"
                    frameborder="0">

            </iframe>
            <div class="pageeditor-frame-bottom"></div>
        </div>
        <?php
    }
}