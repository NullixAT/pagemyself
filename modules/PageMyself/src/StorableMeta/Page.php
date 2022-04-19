<?php

namespace Framelix\PageMyself\StorableMeta;

use Framelix\Framelix\Form\Field\Password;
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
     * @var \Framelix\PageMyself\Storable\Page
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty('category');
        $field = new Select();
        foreach (\Framelix\PageMyself\Storable\Page::$categories as $category) {
            $field->addOption($category, Lang::concatKeys($property->getLabel(), $category));
        }
        $property->field = $field;

        $property = $this->createProperty('title');
        $property->addDefaultField();

        $property = $this->createProperty('link');
        $field = $property->addDefaultField();
        $field->required = true;
        $field->getVisibilityCondition()
            ->equal('category', \Framelix\PageMyself\Storable\Page::CATEGORY_EXTERNAL);

        $property = $this->createProperty('url');
        $field = $property->addDefaultField();
        $field->getVisibilityCondition()
            ->equal('category', \Framelix\PageMyself\Storable\Page::CATEGORY_PAGE);

        $property = $this->createProperty('password');
        $property->field = new Password();
        $property->setVisibility(self::CONTEXT_TABLE, false);
        $property->field->getVisibilityCondition()
            ->equal('category', \Framelix\PageMyself\Storable\Page::CATEGORY_PAGE);

        $property = $this->createProperty('flagDraft');
        $property->addDefaultField();

        $property = $this->createProperty('flagNav');
        $property->addDefaultField();

        $property = $this->createProperty('navGroup');
        $property->addDefaultField();

        $this->addDefaultPropertiesAtEnd();
    }
}