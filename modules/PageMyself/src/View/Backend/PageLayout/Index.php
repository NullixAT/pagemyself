<?php

namespace Framelix\PageMyself\View\Backend\PageLayout;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Storable\PageLayout;

/**
 * Tab view
 */
class Index extends View
{

    /**
     * The storable
     * @var PageLayout
     */
    private PageLayout $storable;

    /**
     * The storable meta
     * @var \Framelix\PageMyself\StorableMeta\PageLayout
     */
    private \Framelix\PageMyself\StorableMeta\PageLayout $meta;


    /**
     * On request
     */
    public function onRequest(): void
    {
        // just to create default if not yet exist
        PageLayout::getDefault();
        $this->storable = PageLayout::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
        }

        $this->meta = new \Framelix\PageMyself\StorableMeta\PageLayout($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            $this->storable->store();
            $otherLayouts = PageLayout::getByCondition('flagDefault = 1', [$this->storable]);
            // there can only be and must only be one default
            if (!count($otherLayouts)) {
                $this->storable->flagDefault = true;
                $this->storable->store();
            } elseif (count($otherLayouts) > 1) {
                array_shift($otherLayouts);
                foreach ($otherLayouts as $layout) {
                    $layout->preserveUpdateUserAndTime();
                    $layout->flagDefault = false;
                    $layout->store();
                }
            }
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->setParameter('id', $this->storable)->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $form = $this->meta->getEditForm();
        $form->show();

        $objects = PageLayout::getByCondition();
        $this->meta->getTable($objects)->show();
    }
}