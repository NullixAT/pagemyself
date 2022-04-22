<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Storable\Storable;

/**
 * Website Settings
 * @property string $key
 * @property mixed $value
 */
class WebsiteSettings extends Storable
{
    /**
     * Internal cache
     * @var self[]|null
     */
    private static ?array $cache = null;

    /**
     * Get setting value
     * @param string $key
     * @return mixed
     */
    public static function get(string $key): mixed
    {
        self::preload();
        return self::$cache[$key]->value ?? null;
    }

    /**
     * Set setting value
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::preload();
        $object = self::$cache[$key] ?? null;
        if (!$object) {
            $object = new self();
            $object->key = $key;
        }
        $object->value = $value;
        $object->store();
        self::$cache[$key] = $object;
    }

    /**
     * Preload all settings at once because almost all settings always will be required
     */
    private static function preload(): void
    {
        if (self::$cache === null) {
            $arr = self::getByCondition();
            self::$cache = [];
            foreach ($arr as $object) {
                self::$cache[$object->key] = $object;
            }
        }
    }
}