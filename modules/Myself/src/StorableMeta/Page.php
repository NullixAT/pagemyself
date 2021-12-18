<?php

namespace Framelix\Myself\StorableMeta;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

/**
 * Page
 */
class Page extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\Myself\Storable\Page
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty('title');
        $property->addDefaultField();

        $property = $this->createProperty('url');
        $property->addDefaultField();

        $property = $this->createProperty('password');
        $property->addDefaultField();

        $property = $this->createProperty('flagDraft');
        $property->addDefaultField();

        $field = new Select();
        $field->searchable = true;
        $field->addOptions(Lang::ISO_LANG_CODES);
        $property = $this->createProperty('lang');
        $property->field = $field;

        $this->addDefaultPropertiesAtEnd();
    }
}