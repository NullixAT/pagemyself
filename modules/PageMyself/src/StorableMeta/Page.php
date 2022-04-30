<?php

namespace Framelix\PageMyself\StorableMeta;

use Framelix\Framelix\Form\Field\Password;
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
        $property->field = new Password();
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $this->addDefaultPropertiesAtEnd();
    }
}