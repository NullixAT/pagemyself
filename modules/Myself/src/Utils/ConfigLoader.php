<?php

namespace Framelix\Myself\Utils;

use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\Storable\WebsiteSettings;

use function explode;
use function file_exists;

use const FRAMELIX_APP_ROOT;

/**
 * ConfigLoader
 */
class ConfigLoader
{
    /**
     * Cache
     * @var array
     */
    private static array $cache = [];

    /**
     * Get additional enabled modules
     * @return string[]
     */
    public static function getModules(): array
    {
        if (!isset(self::$cache[__METHOD__])) {
            $packageJsonRoot = JsonUtils::readFromFile(FRAMELIX_APP_ROOT . "/package.json");
            $currentMajorVersion = (int)explode(".", $packageJsonRoot['version'])[0];
            $modules = WebsiteSettings::get('enabledModules') ?? [];
            // verify if module folder exist
            // prevents enabled modules generate error when have been deleted
            foreach ($modules as $key => $module) {
                $packageJsonFile = FRAMELIX_APP_ROOT . "/modules/$module/package.json";
                if (!file_exists($packageJsonFile)) {
                    unset($modules[$key]);
                } else {
                    // check if module is still supported for current pagemyself version
                    $packageData = JsonUtils::readFromFile($packageJsonFile)['pagemyself'] ?? null;
                    if ($packageData) {
                        if ($currentMajorVersion < $packageData['minMajorVersion'] || $currentMajorVersion > $packageData['maxMajorVersion']) {
                            unset($modules[$key]);
                        }
                    }
                }
            }
            self::$cache[__METHOD__] = $modules;
        }
        return self::$cache[__METHOD__];
    }
}