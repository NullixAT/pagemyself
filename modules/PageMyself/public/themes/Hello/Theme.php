<?php

namespace Framelix\PageMyself\Themes\Hello;

use Framelix\PageMyself\ThemeBase;

/**
 * Hello
 */
class Theme extends ThemeBase
{
    /**
     * Show the page content
     */
    public function showContent(): void
    {
        ?>
        <div class="page">
            <div class="page-inner">
                <?
                $this->showNavigation();
                $this->showComponentBlocks('content');
                ?>
            </div>
        </div>
        <?
    }
}