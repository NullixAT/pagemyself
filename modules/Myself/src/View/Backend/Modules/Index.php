<?php

namespace Framelix\Myself\View\Backend\Modules;

use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\View\Backend\View;

/**
 * Index
 */
class Index extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,content";

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'save':

                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        echo '<div class="framelix-alert">' . Lang::get('__myself_view_backend_modules_info__') . '</div>';
    }
}