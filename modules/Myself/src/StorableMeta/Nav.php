<?php

namespace Framelix\Myself\StorableMeta;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

/**
 * Nav
 */
class Nav extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\Myself\Storable\Nav
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
        $property->valueCallable = function () {
            if ($this->context === self::CONTEXT_TABLE) {
                return $this->storable->getLabel();
            }
            return $this->storable->title;
        };

        $pages = \Framelix\Myself\Storable\Page::getByCondition(sort: ['+title']);
        $field = new Select();
        $field->searchable = true;
        $field->addOption('custom', Lang::get('__myself_storable_nav_link_label__'));
        $field->addOptionsByStorables($pages);
        $property = $this->createProperty('page');
        $property->field = $field;
        $property->valueCallable = function () {
            if (!$this->storable->page) {
                return $this->storable->link ? 'custom' : null;
            }
            return $this->storable->page;
        };

        $property = $this->createProperty('link');
        $property->addDefaultField();
        $property->field->required = true;
        $property->field->getVisibilityCondition()->equal('page', 'custom');

        $field = new Select();
        $field->addOption('_self', Lang::get('__myself_storable_nav_target_self__'));
        $field->addOption('_blank', Lang::get('__myself_storable_nav_target_blank__'));
        $property = $this->createProperty('target');
        $property->field = $field;

        $property = $this->createProperty('flagDraft');
        $property->addDefaultField();

        $field = new Select();
        $field->addOptionsByStorables(
            \Framelix\Myself\Storable\Tag::getByCondition(
                'category = {0}',
                [\Framelix\Myself\Storable\Tag::CATEGORY_PAGE],
                "+sort"
            )
        );
        $property = $this->createProperty('pageTagsVisible');
        $property->field = $field;

        $field = new Select();
        $field->addOptionsByStorables(
            \Framelix\Myself\Storable\Tag::getByCondition(
                'category = {0}',
                [\Framelix\Myself\Storable\Tag::CATEGORY_NAV],
                "+sort"
            )
        );
        $property = $this->createProperty('tags');
        $property->field = $field;

        $this->addDefaultPropertiesAtEnd();
    }
}