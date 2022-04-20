<?php

namespace Framelix\PageMyself\PageBlock;

use Framelix\Framelix\Lang;
use Framelix\PageMyself\Storable\PageBlock;

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
     * Cache
     * @var array
     */
    private static array $cache = [];

    /**
     * The attached page block
     * @var PageBlock
     */
    public PageBlock $block;

    /**
     * Get list of available page blocks
     * @return array
     */
    public static function getAvailableList(): array
    {
        $cacheKey = __METHOD__;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        $files = scandir(__DIR__);
        $arr = [];
        foreach ($files as $file) {
            if ($file[0] === "." || $file === 'Base.php' || !str_ends_with($file, ".php")) {
                continue;
            }
            $blockName = substr($file, 0, -4);
            $class = "Framelix\\PageMyself\\PageBlock\\" . $blockName;
            $langPrefix = '__pagemyself_pageblock_' . strtolower($blockName);
            $arr[$class] = [
                'blockClass' => $class,
                'title' => "{$langPrefix}_title__",
                'desc' => "{$langPrefix}_desc__",
                'help' => Lang::keyExist("{$langPrefix}_help__") ? "{$langPrefix}_help__" : null
            ];
        }
        self::$cache[$cacheKey] = $arr;
        return $arr;
    }

    /**
     * Show content for this block
     * @return void
     */
    abstract public function show(): void;

}