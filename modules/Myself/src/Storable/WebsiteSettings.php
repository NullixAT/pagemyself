<?php

namespace Framelix\Myself\Storable;

use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Utils\ArrayUtils;

/**
 * WebsiteSettings
 * @property mixed|null $settings
 */
class WebsiteSettings extends StorableExtended
{
    /**
     * Interal cache
     * @var null
     */
    private static $cache = null;

    /**
     * Get the website settings instance
     * @return WebsiteSettings
     */
    public static function getInstance(): WebsiteSettings
    {
        if (!self::$cache) {
            self::$cache = self::getByConditionOne();
            if (!self::$cache) {
                self::$cache = new self();
                self::$cache->store();
            }
        }
        return self::$cache;
    }

    /**
     * Get setting
     * @param string $key
     * @return mixed
     */
    public static function get(string $key): mixed
    {
        $instance = self::getInstance();
        $settings = $instance->settings ?? [];
        return ArrayUtils::getValue($settings, $key);
    }

    /**
     * Set setting
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $instance = self::getInstance();
        $settings = $instance->settings ?? [];
        ArrayUtils::setValue($settings, $key, $value);
        $instance->settings = $settings;
        $instance->store();
    }
}