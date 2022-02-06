<?php

namespace Framelix\Myself\Utils;

use Framelix\Framelix\Lang;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\VersionUtils;
use Framelix\Myself\Storable\WebsiteSettings;

use function file_exists;
use function scandir;
use function strtolower;

use const FRAMELIX_APP_ROOT;

/**
 * ModuleUtils
 */
class ModuleUtils
{
    public const MODULE_UPDATE_CACHE_FILE = __DIR__ . "/../../tmp/module-update-available.json";

    /**
     * Cache
     * @var array
     */
    private static array $cache = [];

    /**
     * Get store data
     * @return array
     */
    public static function getStoreData(): array
    {
        $browser = Browser::create();
        $browser->validateSsl = false;
        $browser->url = 'https://nullixat.github.io/pagemyself-module-store/modulelist.json';
        $browser->sendRequest();
        return $browser->getResponseJson() ?? [];
    }

    /**
     * GHet all infos to all installed modules, enabled or disabled
     * @return array
     */
    public static function getInstalledData(): array
    {
        // get installed modules
        $folders = scandir(FRAMELIX_APP_ROOT . "/modules");
        Lang::loadValues(Lang::$lang);
        $moduleList = [];
        foreach ($folders as $folder) {
            if ($folder === "Framelix" || $folder === "Myself") {
                continue;
            }
            $moduleFolder = FRAMELIX_APP_ROOT . "/modules/$folder";
            $packageJson = "$moduleFolder/package.json";
            if (file_exists($packageJson)) {
                $moduleLower = strtolower($folder);
                $packageJsonData = JsonUtils::readFromFile($packageJson);
                $moduleData = JsonUtils::readFromFile($packageJson)['pagemyself'] ?? null;
                if (!$moduleData) {
                    continue;
                }
                $moduleData['module'] = $folder;
                $moduleData['version'] = $packageJsonData['version'];
                if (isset($packageJsonData['homepage'])) {
                    $moduleData['homepage'] = $packageJsonData['homepage'];
                }
                Lang::loadValues(Lang::$lang, $folder);
                Lang::loadValues("en", $folder);
                $moduleData['lang'][Lang::$lang]['name'] = Lang::get('__' . $moduleLower . "_module_name__");
                $moduleData['lang'][Lang::$lang]['info'] = Lang::get('__' . $moduleLower . "_module_info__");
                $moduleList[$folder] = $moduleData;
            }
        }
        return $moduleList;
    }

    /**
     * Get additional enabled modules
     * @return string[]
     */
    public static function getModules(): array
    {
        if (!isset(self::$cache[__METHOD__])) {
            $packageJsonRoot = JsonUtils::getPackageJson(null);
            $currentMajorVersion = VersionUtils::splitVersionString($packageJsonRoot['version'])["major"];
            $modules = WebsiteSettings::get('enabledModules') ?? [];
            // verify if module folder exist
            // prevents enabled modules generate error when have been deleted
            foreach ($modules as $key => $module) {
                $packageJson = JsonUtils::getPackageJson($module);
                if (!$packageJson || !($packageJson['pagemyself'] ?? null)) {
                    unset($modules[$key]);
                } elseif ($currentMajorVersion < $packageJson['pagemyself']['minMajorVersion'] || $currentMajorVersion > $packageJson['pagemyself']['maxMajorVersion']) {
                    unset($modules[$key]);
                }
            }
            self::$cache[__METHOD__] = $modules;
        }
        return self::$cache[__METHOD__];
    }
}