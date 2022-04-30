<?php

namespace Framelix\PageMyself\StorableMeta;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;
use Framelix\PageMyself\Form\Field\MediaBrowser;

/**
 * NavEntry
 */
class NavEntry extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\PageMyself\Storable\NavEntry
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();


        $property = $this->createProperty('image');
        $property->field = new MediaBrowser();
        $property->field->setOnlyImages();
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty('title');
        $property->addDefaultField();

        $property = $this->createProperty('groupTitle');
        $property->addDefaultField();

        $property = $this->createProperty('page');
        $field = new Select();
        $property->field = $field;
        $pages = \Framelix\PageMyself\Storable\Page::getByCondition(sort: "+sort");
        foreach ($pages as $page) {
            $field->addOption($page, $page->title);
        }
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty('url_external');
        $property->field = new Text();
        $property->field->getVisibilityCondition()->empty('page');
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty('url_hash');
        $property->field = new Text();
        $property->field->getVisibilityCondition()->notEmpty('page');
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty('flagShow');
        $property->addDefaultField();

        $this->addDefaultPropertiesAtEnd();
    }
}