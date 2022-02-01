<?php

namespace Framelix\Myself;

use Framelix\Framelix\Config;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\VersionUtils;
use Framelix\Myself\Utils\ConfigLoader;
use Framelix\Myself\Utils\ModuleUtils;

use function basename;
use function class_exists;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function preg_match;
use function realpath;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;
use function unlink;
use function version_compare;

/**
 * Console Runner
 */
class Console extends \Framelix\Framelix\Console
{
    /**
     * Create a new module with empty boilerplate
     * @return int Status Code, 0 = success
     */
    public static function createModule(): int
    {
        $moduleName = self::getParameter('module', 'string');
        if (preg_match("~[^a-z0-9]~i", $moduleName)) {
            echo "Modulename can only contain A-Z and 0-9 chars";
            return 1;
        }
        if (!preg_match("~^[A-Z]~", $moduleName)) {
            echo "Modulename must start with an uppercase character";
            return 1;
        }
        $moduleDir = __DIR__ . "/../../$moduleName";
        if (is_dir($moduleDir)) {
            echo "Module Directory " . realpath($moduleDir) . " already exists";
            return 1;
        }
        mkdir($moduleDir);
        mkdir($moduleDir . "/_meta");
        mkdir($moduleDir . "/config");
        mkdir($moduleDir . "/lang");
        mkdir($moduleDir . "/js", 0777, true);
        file_put_contents($moduleDir . "/lang/en.json", '{}');
        mkdir($moduleDir . "/public/dist/css", 0777, true);
        mkdir($moduleDir . "/public/dist/js", 0777, true);
        mkdir($moduleDir . "/scss", 0777, true);
        mkdir($moduleDir . "/src", 0777, true);
        Config::writetConfigToFile($moduleName, "config-module.php", [
            'compiler' => [
                $moduleName => [
                    'js' => [],
                    'scss' => [],
                ]
            ]
        ]);
        $myselfConfig = Config::getConfigFromFile("Myself", "config-editable.php");
        $myselfConfig['modules'][$moduleName] = $moduleName;
        Config::writetConfigToFile("Myself", "config-editable.php", $myselfConfig);
        $packageJsonRoot = JsonUtils::getPackageJson(null);
        $currentMajorVersion = VersionUtils::splitVersionString($packageJsonRoot['version'])["major"];
        JsonUtils::writeToFile($moduleDir . "/package.json", [
            "version" => "0.0.1",
            "name" => "pagemyself-" . strtolower($moduleName),
            "description" => "Your module description",
            "framelix" => [
                "module" => $moduleName
            ],
            "pagemyself" => [
                "minMajorVersion" => $currentMajorVersion,
                "maxMajorVersion" => $currentMajorVersion,
                "categories" => [

                ]
            ],
        ], true);
        return 0;
    }

    /**
     * Create a new theme with empty boilerplate
     * @return int Status Code, 0 = success
     */
    public static function createTheme(): int
    {
        $module = self::getParameter('module', 'string');
        $themeName = self::getParameter('theme', 'string');
        $themeNameLower = strtolower($themeName);
        $themeClass = "\\Framelix\\$module\\PageBlocks\\$themeName";
        $moduleDir = FileUtils::getModuleRootPath($module);
        if (class_exists($themeClass)) {
            echo "'$themeClass' already exists";
            return 1;
        }

        if (!is_dir($moduleDir . "/js/themes/$themeNameLower")) {
            mkdir($moduleDir . "/js/themes/$themeNameLower", 0777, true);
        }
        if (!is_dir($moduleDir . "/scss/themes/$themeNameLower")) {
            mkdir($moduleDir . "/scss/themes/$themeNameLower", 0777, true);
        }
        if (!is_dir($moduleDir . "/src/Themes")) {
            mkdir($moduleDir . "/src/Themes", 0777, true);
        }
        if (!is_dir($moduleDir . "/public/themes/$themeNameLower")) {
            mkdir($moduleDir . "/public/themes/$themeNameLower", 0777, true);
        }
        $templateContent = file_get_contents(__DIR__ . "/../templates/Theme.php");
        $templateContent = str_replace("__THEMENAME__", $themeName, $templateContent);
        $templateContent = str_replace("__MODULE__", $module, $templateContent);
        $path = $moduleDir . "/src/Themes/$themeName.php";
        file_put_contents($path, $templateContent);

        $path = $moduleDir . "/scss/themes/$themeNameLower/style.scss";
        file_put_contents($path, '');

        $path = $moduleDir . "/js/themes/$themeNameLower/script.js";
        $templateContent = file_get_contents(__DIR__ . "/../templates/Theme.js");
        $templateContent = str_replace("__THEMENAMEJS__", $module . "Theme" . $themeName, $templateContent);
        file_put_contents($path, $templateContent);

        self::updateCompilerConfig();
        return 0;
    }

    /**
     * Create a new pageblock with empty boilerplate
     * @return int Status Code, 0 = success
     */
    public static function createPageBlock(): int
    {
        $module = self::getParameter('module', 'string');
        $pageBlockName = self::getParameter('pageBlockName', 'string');
        $pageBlockNameLower = strtolower($pageBlockName);
        $moduleDir = FileUtils::getModuleRootPath($module);
        if (!is_dir($moduleDir)) {
            echo "Module '$module' not exist";
            return 1;
        }
        $blockClass = "\\Framelix\\$module\\PageBlocks\\$pageBlockName";
        if (class_exists($blockClass)) {
            echo "'$blockClass' already exists";
            return 1;
        }

        if (!is_dir($moduleDir . "/js/page-blocks")) {
            mkdir($moduleDir . "/js/page-blocks", 0777, true);
        }
        if (!is_dir($moduleDir . "/scss/page-blocks")) {
            mkdir($moduleDir . "/scss/page-blocks", 0777, true);
        }
        if (!is_dir($moduleDir . "/src/PageBlocks")) {
            mkdir($moduleDir . "/src/PageBlocks", 0777, true);
        }
        $templateContent = file_get_contents(__DIR__ . "/../templates/PageBlock.php");
        $templateContent = str_replace("__BLOCKNAME__", $pageBlockName, $templateContent);
        $templateContent = str_replace("__MODULE__", $module, $templateContent);
        $path = $moduleDir . "/src/PageBlocks/$pageBlockName.php";
        file_put_contents($path, $templateContent);

        $templateContent = file_get_contents(__DIR__ . "/../templates/PageBlock.js");
        $templateContent = str_replace("__BLOCKNAMEJS__", $module . "PageBlocks" . $pageBlockName, $templateContent);
        $path = $moduleDir . "/js/page-blocks/$pageBlockNameLower/script.js";
        mkdir(dirname($path));
        file_put_contents($path, $templateContent);

        $templateContent = file_get_contents(__DIR__ . "/../templates/PageBlock.scss");
        $templateContent = str_replace(
            "__BLOCKNAMESCSS__",
            strtolower($module) . "-pageblocks-" . $pageBlockNameLower,
            $templateContent
        );
        $path = $moduleDir . "/scss/page-blocks/$pageBlockNameLower/style.scss";
        mkdir(dirname($path));
        file_put_contents($path, $templateContent);
        self::updateCompilerConfig();
        return 0;
    }

    /**
     * Update compiler config based on available page blocks and themes
     * @return int Status Code, 0 = success
     */
    public static function updateCompilerConfig(): int
    {
        $module = self::getParameter('module', 'string');
        $moduleDir = FileUtils::getModuleRootPath($module);
        if (!is_dir($moduleDir)) {
            echo "Module '$module' not exist";
            return 1;
        }
        $config = $configOriginal = Config::getConfigFromFile($module, "config-module.php");
        if (isset($config['compiler'][$module])) {
            foreach ($config['compiler'][$module] as $type => $rows) {
                foreach ($rows as $key => $row) {
                    if (str_starts_with($key, "pageblock-") || str_starts_with($key, "theme-")) {
                        unset($config['compiler'][$module][$type][$key]);
                    }
                }
            }
        }
        $pageBlockFiles = FileUtils::getFiles($moduleDir . "/src/PageBlocks", "~\.php$~", false);
        foreach ($pageBlockFiles as $pageBlockFile) {
            $basename = basename($pageBlockFile);
            if ($basename === "BlockBase.php") {
                continue;
            }
            $blockName = substr(strtolower($basename), 0, -4);
            $jsFolder = $moduleDir . "/js/page-blocks/$blockName";
            if (is_dir($jsFolder)) {
                $config['compiler'][$module]['js']["pageblock-$blockName"] = [
                    "files" => [
                        [
                            "type" => "folder",
                            "path" => "js/page-blocks/$blockName",
                            "recursive" => true
                        ]
                    ],
                    "options" => ["noInclude" => true]
                ];
            }
            $scssFolder = $moduleDir . "/scss/page-blocks/$blockName";
            if (is_dir($scssFolder)) {
                $config['compiler'][$module]['scss']["pageblock-$blockName"] = [
                    "files" => [
                        [
                            "type" => "folder",
                            "path" => "scss/page-blocks/$blockName",
                            "recursive" => true
                        ]
                    ],
                    "options" => ["noInclude" => true]
                ];
            }
        }
        $themeFiles = FileUtils::getFiles($moduleDir . "/src/Themes", "~\.php$~", false);
        foreach ($themeFiles as $themeFile) {
            $basename = basename($themeFile);
            if ($basename === "ThemeBase.php") {
                continue;
            }
            $blockName = substr(strtolower($basename), 0, -4);
            $jsFolder = $moduleDir . "/js/themes/$blockName";
            if (is_dir($jsFolder)) {
                $config['compiler'][$module]['js']["theme-$blockName"] = [
                    "files" => [
                        [
                            "type" => "folder",
                            "path" => "js/themes/$blockName",
                            "recursive" => true
                        ]
                    ],
                    "options" => ["noInclude" => true]
                ];
            }
            $scssFolder = $moduleDir . "/scss/themes/$blockName";
            if (is_dir($scssFolder)) {
                $config['compiler'][$module]['scss']["theme-$blockName"] = [
                    "files" => [
                        [
                            "type" => "folder",
                            "path" => "scss/themes/$blockName",
                            "recursive" => true
                        ]
                    ],
                    "options" => ["noInclude" => true]
                ];
            }
        }
        if ($config !== $configOriginal) {
            Config::writetConfigToFile($module, "config-module.php", $config);
            echo "Updated config for $module";
        } else {
            echo "Config is already Up2Date";
        }
        return 0;
    }

    /**
     * Check for module updates
     * @return int Status Code, 0 = success
     */
    public static function checkModuleUpdates(): int
    {
        $installedModules = ModuleUtils::getInstalledData();
        $storeModules = ModuleUtils::getStoreData();
        if (file_exists(ModuleUtils::MODULE_UPDATE_CACHE_FILE)) {
            unlink(ModuleUtils::MODULE_UPDATE_CACHE_FILE);
        }
        foreach ($installedModules as $module => $row) {
            if (!isset($storeModules[$module])) {
                continue;
            }
            if (version_compare($row['version'], $storeModules[$module]['version'], '<')) {
                self::line(
                    'Update available for ' . $module . ' - Available: ' . $storeModules[$module]['version'] . ' | Installed: ' . $row['version']
                );
                JsonUtils::writeToFile(ModuleUtils::MODULE_UPDATE_CACHE_FILE, 1);
            } else {
                self::line($module . ' is Up2Date');
            }
        }
        return 0;
    }

    /**
     * All the after install hook for given module
     * @return int Status Code, 0 = success
     */
    public static function callAfterInstallHook(): int
    {
        $module = self::getParameter('module', 'string');
        ModuleHooks::callHook('afterInstall', [], $module);
        return 0;
    }
}