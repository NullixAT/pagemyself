<?php

namespace Framelix\Myself;

use Framelix\Framelix\Console;
use Framelix\Framelix\Storable\Mutex;

/**
 * Cron Runner
 */
class Cron extends Console
{
    /**
     * Run cronjob
     */
    public static function runCron(): void
    {
        // module update check every hour
        if (self::getParameter('forceUpdateCheck') || !Mutex::isLocked('myself-cron', 3600)) {
            if (!self::getParameter('forceUpdateCheck')) {
                Mutex::create('myself-cron');
            }
            self::checkModuleUpdates();
        }
    }

    /**
     * Check for module updates
     * @return int Status Code, 0 = success
     */
    public static function checkModuleUpdates(): int
    {
        // todo integrate
        return 0;
    }
}