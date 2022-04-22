<?php

namespace Framelix\PageMyself\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Network\JsCall;
use function is_array;

/**
 * MediaBrowser
 */
class MediaBrowser extends Field
{
    /**
     * Allowed file extensions
     * @var string[]|null
     */
    public ?array $allowedExtensions = null;

    /**
     * Allow multiple select
     * @var bool
     */
    public bool $multiple = false;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'list':

                break;
        }
    }

    /**
     * Set allowing only images
     * @return void
     */
    public function setOnlyImages(): void
    {
        $this->allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'webp'];
    }

    /**
     * Set allowing only videos
     * @return void
     */
    public function setOnlyVideos(): void
    {
        $this->allowedExtensions = ['mp4', 'webm'];
    }

    /**
     * Get converted submitted value
     * @return string|array|int|null
     */
    public function getDefaultConvertedSubmittedValue(): string|array|int|null
    {
        $value = $this->getSubmittedValue();
        if (is_array($value)) {
            foreach ($value as $key => $v) {
                $value[$key] = (int)$v;
            }
            return $value;
        }
        if ($value) {
            return (int)$value;
        }
        return null;
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['properties']['apiUrl'] = JsCall::getCallUrl(
            __CLASS__,
            'list',
            ['allowedExtensions' => $this->allowedExtensions]
        );
        return $data;
    }


}