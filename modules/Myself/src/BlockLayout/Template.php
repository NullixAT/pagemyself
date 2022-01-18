<?php

namespace Framelix\Myself\BlockLayout;

use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\Themes\ThemeBase;
use JsonSerializable;

use function file_exists;

/**
 * Template
 */
class Template implements JsonSerializable
{
    /**
     * The label for the user interface
     * @var string[]|null
     */
    public ?array $label = null;

    /**
     * The description for the user interface
     * @var string[]|null
     */
    public ?array $description = null;

    /**
     * The file extension for the image thumbnail
     * @var string|null
     */
    public ?string $thumbnailExtension = null;

    /**
     * Block layout
     * @var BlockLayout
     */
    public BlockLayout $blockLayout;

    /**
     * Page block data
     * @var array
     */
    public array $pageBlockData;

    /**
     * Constructor
     * @param ThemeBase $themeBase
     * @param string|null $templateFilename
     */
    public function __construct(
        public ThemeBase $themeBase,
        public ?string $templateFilename = null
    ) {
        $this->blockLayout = BlockLayout::create(null);
        if ($this->templateFilename) {
            $themeFolder = $themeBase->getThemePublicFolderPath();
            $templateFile = $themeFolder . "/" . $this->templateFilename . ".json";
            if (file_exists($templateFile)) {
                $templateData = JsonUtils::readFromFile($themeFolder . "/" . $this->templateFilename . ".json");
                foreach ($templateData as $key => $value) {
                    if ($key === 'blockLayout') {
                        $this->blockLayout = BlockLayout::create($value);
                    } else {
                        $this->{$key} = $value;
                    }
                }
            }
        }
    }

    /**
     * Get thumbnail path
     * @return string|null
     */
    public function getThumbnailPath(): ?string
    {
        if ($this->templateFilename && $this->thumbnailExtension) {
            $thumbnailFile = $this->themeBase->getThemePublicFolderPath() . "/"
                . $this->templateFilename . "." . $this->thumbnailExtension;
            if (file_exists($thumbnailFile)) {
                return $thumbnailFile;
            }
        }
        return null;
    }

    /**
     * Json serialize
     * @return array
     */
    public function jsonSerialize(): array
    {
        $arr = (array)$this;
        unset($arr['themeBase'], $arr['templateFilename']);
        return $arr;
    }


}