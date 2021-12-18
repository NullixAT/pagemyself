<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View\LayoutView;

use const FRAMELIX_MODULE;

/**
 * PageMyselfAbout
 */
class PageMyselfAbout extends LayoutView
{

    /**
     * Is in editmode
     * @var bool
     */
    public bool $editMode = false;

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Multilanguage disable
     * @var bool
     */
    protected bool $multilanguage = false;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->includeCompiledFilesForModule("Framelix");
        $this->includeCompiledFilesForModule(FRAMELIX_MODULE);
        $this->includeCompiledFile(FRAMELIX_MODULE, "scss", "myself");
        $this->includeCompiledFile(FRAMELIX_MODULE, "js", "myself");
        $this->pageTitle = "This website is generated with PageMyself";
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        ?>
        <div class="main">
            <img src="<?= Url::getUrlToFile(__DIR__ . "/../../public/img/logo-colored-black.svg") ?>" alt="PageMyself"
                 style="max-width: 80%" width="500">
            <h1>A full WYSIWYG website builder with live editing features</h1>
            <p>
                This page was generated with the open source website builder <a
                        href="https://github.com/brainfoolong/pagemyself" target="_blank">PageMyself
                    - A full WYSIWYG website builder with live editing features</a>.
            </p>
        </div>
        <style>
          body {
            background: #f5f5f5;
            text-align: center;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
          }
          .main {
            margin: 0 auto;
            max-width: 800px;
            background: white;
            padding: 40px;
            box-shadow: rgba(0, 0, 0, 0.1) 0 0 30px;
            border-radius: 40px;
          }
        </style>
        <?php
    }

    /**
     * Show content with page layout
     * @return void
     */
    public function showContentWithLayout(): void
    {
        Buffer::start();
        echo '<!DOCTYPE html>';
        echo '<html lang="en" data-color-scheme-force="light">';
        $this->showDefaultPageStartHtml();
        echo '<body>';
        echo '<div class="framelix-page">';
        $this->showContent();
        echo '</div>';
        ?>
        <script>
          Framelix.initLate()
        </script>
        <?
        echo '</body></html>';
        Buffer::flush();
    }
}