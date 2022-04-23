<?php

namespace Framelix\PageMyself\Storable;

use Framelix\Framelix\Date;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;

/**
 * ComponentCalendarEntry
 * @property Date $date
 * @property string|null $color
 * @property string|null $info
 * @property string|null $internalInfo
 */
class ComponentCalendarEntry extends StorableExtended
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