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

        $field = new Select();
        $field->required = true;
        foreach (\Framelix\Myself\Storable\Nav::LINKTYPES as $type) {
            $field->addOption($type, Lang::get('__myself_storable_nav_linktype_' . $type . '__'));
        }
        $property = $this->createProperty('linkType');
        $property->field = $field;

        $pages = \Framelix\Myself\Storable\Page::getByCondition(sort: ['+title']);
        $field = new Select();
        $field->searchable = true;
        $field->required = true;
        $field->addOptionsByStorables($pages);
        $property = $this->createProperty('page');
        $property->field = $field;
        $property->field->getVisibilityCondition()->equal('linkType', \Framelix\Myself\Storable\Nav::LINKTYPE_PAGE);

        $property = $this->createProperty('link');
        $property->addDefaultField();
        $property->field->required = true;
        $property->field->getVisibilityCondition()->equal('linkType', \Framelix\Myself\Storable\Nav::LINKTYPE_CUSTOM);

        $field = new Select();
        $field->addOption('_self', Lang::get('__myself_storable_nav_target_self__'));
        $field->addOption('_blank', Lang::get('__myself_storable_nav_target_blank__'));
        $property = $this->createProperty('target');
        $property->field = $field;

        $property = $this->createProperty('flagDraft');
        $property->addDefaultField();

        $existingPages = \Framelix\Myself\Storable\Page::getByCondition();
        $langCodes = [];
        foreach ($existingPages as $existingPage) {
            $langCodes[$existingPage->lang] = Lang::ISO_LANG_CODES[$existingPage->lang];
        }
        $field = new Select();
        $field->searchable = true;
        $field->addOptions($langCodes);
        $property = $this->createProperty('lang');
        $property->field = $field;

        $this->addDefaultPropertiesAtEnd();
    }
}