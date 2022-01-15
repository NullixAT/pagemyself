<?php

namespace Framelix\Myself\Utils;

use Framelix\Myself\Storable\WebsiteSettings;

/**
 * ConfigLoader
 */
class ConfigLoader
{
    /**
     * Get additional enabled modules
     * @return string[]
     */
    public static function getModules(): array
    {
        return WebsiteSettings::get('enabledModules') ?? [];
    }
}