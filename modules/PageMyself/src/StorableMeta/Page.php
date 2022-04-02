<?php

namespace Framelix\PageMyself\StorableMeta;

use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

/**
 * Page
 */
class Page extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\PageMyself\Storable\Page
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

        $property = $this->createProperty('flagNav');
        $property->addDefaultField();

        $property = $this->createProperty('flagNewTab');
        $property->addDefaultField();

        $this->addDefaultPropertiesAtEnd();
    }
}