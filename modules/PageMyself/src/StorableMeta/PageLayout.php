<?php

namespace Framelix\PageMyself\StorableMeta;

use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

/**
 * PageLayout
 */
class PageLayout extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\PageMyself\Storable\PageLayout
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

        $property = $this->createProperty('flagDefault');
        $property->addDefaultField();

        $property = $this->createProperty('layoutSettings[design]');
        $field = new Select();
        $field->required = true;
        $field->addOption('default', '__pagemyself_editor_pagelayout_design_default__');
        $field->addOption('dark', '__pagemyself_editor_pagelayout_design_dark__');
        $property->field = $field;

        $property = $this->createProperty('layoutSettings[align]');
        $field = new Select();
        $field->required = true;
        $field->addOption('left', '__pagemyself_editor_pagelayout_align_left__');
        $field->addOption('center', '__pagemyself_editor_pagelayout_align_center__');
        $property->field = $field;

        $property = $this->createProperty('layoutSettings[nav]');
        $field = new Select();
        $field->required = true;
        $field->addOption('none', '__pagemyself_editor_pagelayout_nav_none__');
        $field->addOption('top', '__pagemyself_editor_pagelayout_nav_top__');
        $field->addOption('left', '__pagemyself_editor_pagelayout_nav_left__');
        $property->field = $field;

        $property = $this->createProperty('layoutSettings[maxWidth]');
        $field = new Number();
        $field->min = 400;
        $field->max = 10000;
        $field->setIntegerOnly();
        $field->required = true;
        $property->field = $field;

        $this->addDefaultPropertiesAtEnd();
    }
}