<?php

namespace Framelix\PageMyself\Component;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\PageMyself\Storable\ComponentBlock;

use function scandir;
use function str_ends_with;
use function strtolower;
use function substr;

/**
 * Base class for a component
 */
abstract class ComponentBase
{
    /**
     * Cache
     * @var array
     */
    private static array $cache = [];

    /**
     * The attached page block
     * @var ComponentBlock
     */
    public ComponentBlock $block;

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
            if ($file[0] === "." || $file === 'ComponentBase.php' || !str_ends_with($file, ".php")) {
                continue;
            }
            $blockName = substr($file, 0, -4);
            $class = "Framelix\\PageMyself\\Component\\" . $blockName;
            $langPrefix = '__pagemyself_component_' . strtolower($blockName);
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
     * Create an instance based on the block
     * @param ComponentBlock $componentBlock
     * @return ComponentBase
     */
    public static function createInstance(ComponentBlock $componentBlock): ComponentBase
    {
        /** @var ComponentBase $instance */
        $instance = new $componentBlock->blockClass();
        $instance->block = $componentBlock;
        return $instance;
    }

    /**
     * Get default settings for this block
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [];
    }

    /**
     * Add setting fields to the settings form that is displayed when the user click the settings icon
     */
    public function addSettingFields(Form $form): void
    {
    }

    /**
     * Show content for this block
     * @return void
     */
    abstract public function show(): void;

}