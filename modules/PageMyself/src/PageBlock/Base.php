<?php

namespace Framelix\PageMyself\PageBlock;

use function scandir;
use function str_ends_with;
use function strtolower;
use function substr;

/**
 * Base class for page block
 */
abstract class Base
{
    /**
     * Get list of available page blocks
     * @return array
     */
    public static function getAvailableList(): array
    {
        $files = scandir(__DIR__);
        $arr = [];
        foreach ($files as $file) {
            if ($file[0] === "." || $file === 'Base.php' || !str_ends_with($file, ".php")) {
                continue;
            }
            $blockName = substr($file, 0, -4);
            $class = "\\Framelix\\PageMyself\\PageBlock\\" . $blockName;
            $arr[$class] = [
                'class' => $class,
                'title' => '__pagemyself_pageblock_' . strtolower($blockName) . "_title__",
                'desc' => '__pagemyself_pageblock_' . strtolower($blockName) . "_desc__",
            ];
        }
        return $arr;
    }

    /**
     * Show content for this block
     * @return void
     */
    abstract public function show(): void;

}