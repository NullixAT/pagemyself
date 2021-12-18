<?php

namespace Framelix\Myself;

use Framelix\Framelix\Config;

use function date;

/**
 * Cron Runner
 */
class Cron
{
    /**
     * Run cronjob
     */
    public static function run(): void
    {
        // every hour check for updates
        if ((int)date("i") <= 0) {
            foreach (Config::$loadedModules as $module) {
            }
        }
    }
}