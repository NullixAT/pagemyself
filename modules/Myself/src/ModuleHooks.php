<?php

namespace Framelix\Myself;

use Framelix\Framelix\Config;
use Framelix\Myself\View\Index;

use function call_user_func_array;
use function class_exists;
use function method_exists;

/**
 * ModuleHooks to run when something is happening
 */
class ModuleHooks
{
    /**
     * Call a hook for all installed modules, if hook exist
     * @param string $hookMethod
     * @param array $parameters
     * @param string|null $limitModule If set, only run for this module
     * @return void
     */
    final public static function callHook(string $hookMethod, array $parameters = [], ?string $limitModule = null): void
    {
        foreach (Config::$loadedModules as $module) {
            $hookClass = "\\Framelix\\$module\\ModuleHooks";
            if (!class_exists($hookClass) || !method_exists($hookClass, $hookMethod)) {
                continue;
            }
            if ($limitModule && $limitModule !== $module) {
                continue;
            }
            call_user_func_array([$hookClass, $hookMethod], $parameters);
        }
    }

    /**
     * Executed after the module has been installed via backend
     * @return void
     */
    public static function afterInstall(): void
    {
    }

    /**
     * Executed before the pages content is shown
     * Use this to add metadata for example
     * Do not do jobs here the can impact performance as it is done on every page request
     * @param Index $view
     * @return void
     */
    public static function beforeViewShowContent(Index $view): void
    {
    }
}