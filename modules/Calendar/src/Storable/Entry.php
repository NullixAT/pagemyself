<?php

namespace Framelix\Calendar\Storable;

use Framelix\Framelix\Date;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;

/**
 * Entry
 * @property Date $date
 * @property string|null $color
 * @property string|null $info
 * @property string|null $internalInfo
 */
class Entry extends StorableExtended
{
    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        $selfStorableSchema->properties['internalInfo']->databaseType = 'text';
        $selfStorableSchema->addIndex('date', 'unique');
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }
}