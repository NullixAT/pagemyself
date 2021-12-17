<?php

namespace Framelix\Myself\StorableMeta;

use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

/**
 * Tag
 */
class Tag extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\Myself\Storable\Tag
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty("name");
        $property->addDefaultField();
        $property->valueCallable = function () {
            if ($this->context === self::CONTEXT_TABLE) {
                return $this->storable->getHtmlString();
            }
            return $this->storable->name;
        };

        $property = $this->createProperty("color");
        $property->field = new Color();

        $this->addDefaultPropertiesAtEnd();
    }
}