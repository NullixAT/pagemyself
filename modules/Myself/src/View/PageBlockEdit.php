<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\MediaBrowser;

/**
 * Index
 */
class PageBlockEdit extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,content";

    /**
     * On request
     */
    public function onRequest(): void
    {
        $action = Request::getGet('action');
        switch ($action) {
            case 'getmediabrowserurl':
                echo JsCall::getCallUrl(
                    MediaBrowser::class,
                    'list',
                    [
                        'allowedExtensions' => Request::getGet('allowedExtensions')
                    ]
                );
                break;
            case 'save-editable-content':
                $storable = Storable::getById(Request::getPost('storableId'));
                if ($storable) {
                    if (Request::getPost('arrayKey') === null || Request::getPost('arrayKey') === '') {
                        $storable->{Request::getPost('propertyName')} = Request::getPost('content');
                    } else {
                        $arr = $storable->{Request::getPost('propertyName')} ?? [];
                        ArrayUtils::setValue($arr, Request::getPost('arrayKey'), Request::getPost('content'));
                        $storable->{Request::getPost('propertyName')} = $arr;
                    }
                    $storable->store();
                    echo 1;
                    return;
                }
                echo 0;
                break;
        }
    }
}