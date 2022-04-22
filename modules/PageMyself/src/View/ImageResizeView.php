<?php

namespace Framelix\PageMyself\View;

use Framelix\Framelix\Url;
use Framelix\Framelix\View;
use Gumlet\ImageResize;

/**
 * Index
 */
class ImageResizeView extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Custom url
     * @var string|null
     */
    protected ?string $customUrl = "~/uploads/(?<folder>[0-9]+)/t-(?<thumbSize>100|500|1000|1500|1920)-(?<filename>.*)\.(?<extension>jpg|jpeg|png|gif|webp)$~";

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
        $originalPath = __DIR__ . "/../../public/uploads/" . $this->customUrlParameters['folder'] . "/" . $this->customUrlParameters['filename'] . "." . $this->customUrlParameters['extension'];
        if (!file_exists($originalPath)) {
            http_response_code(404);
            return;
        }
        $thumbSize = (int)$this->customUrlParameters['thumbSize'];
        $thumbPath = __DIR__ . "/../../public/uploads/" . $this->customUrlParameters['folder'] . "/t-$thumbSize-" . $this->customUrlParameters['filename'] . "." . $this->customUrlParameters['extension'];
        require_once __DIR__ . "/../../vendor/php-image-resize/lib/ImageResize.php";
        require_once __DIR__ . "/../../vendor/php-image-resize/lib/ImageResizeException.php";
        $image = new ImageResize($originalPath);

        $image->resizeToBestFit($thumbSize, $thumbSize);
        $image->save($thumbPath);
        Url::getBrowserUrl()->redirect();
    }
}