<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View\LayoutView;

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
        $this->pageTitle = "This website is generated with PageMyself - Open Source Self Hosted WYSIWYG Website Builder";
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        ?>
        <div class="main">
            <a href="https://github.com/NullixAT/pagemyself" target="_blank">
                <img
                        src="<?= Url::getUrlToFile(__DIR__ . "/../../public/img/logo-colored-black.svg") ?>"
                        alt="PageMyself"
                        style="max-width: 80%"
                        width="500">
            </a>
            <h1>
                This page was generated with <a
                        href="https://github.com/NullixAT/pagemyself" target="_blank">PageMyself - Open Source Self
                    Hosted WYSIWYG Website Builder</a>
            </h1>
            <p style="font-size: 90%; opacity:0.8;">
                Our goal is to give you the tools to create your private/company website in no time and without coding
                skills.<br/>
                It is almost as easy as writing an office document.</p>
        </div>
        <style>
          body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #ffffff linear-gradient(45deg, #f5f5f5 25%, #ffffff 25%, #ffffff 50%, #f5f5f5 50%, #f5f5f5 75%, #ffffff 75%, #ffffff 100%);
            background-size: 56px 56px;
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
        <?php
        echo '</body></html>';
        Buffer::flush();
    }
}