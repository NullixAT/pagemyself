<?php

namespace Framelix\Guestbook\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;

/**
 * Entry
 * @property string|null $email
 * @property string|null $name
 * @property string $text
 * @property bool $flagValidated
 */
class Entry extends StorableExtended
{
    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        $selfStorableSchema->properties['text']->databaseType = 'text';
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