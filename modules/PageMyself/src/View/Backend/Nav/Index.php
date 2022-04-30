<?php

namespace Framelix\PageMyself\View\Backend\Nav;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Storable\NavEntry;

/**
 * Tab view
 */
class Index extends View
{

    /**
     * The storable
     * @var NavEntry
     */
    private NavEntry $storable;

    /**
     * The storable meta
     * @var \Framelix\PageMyself\StorableMeta\NavEntry
     */
    private \Framelix\PageMyself\StorableMeta\NavEntry $meta;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = NavEntry::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
            $this->storable->flagShow = true;
        }

        $this->meta = new \Framelix\PageMyself\StorableMeta\NavEntry($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            if (!$this->storable->page && !str_starts_with($this->storable->url ?? '', 'http')) {
                Response::showFormValidationErrorResponse('__pagemyself_storable_naventry_missing__');
            }
            $this->storable->url = (string)$this->storable->url;
            $this->storable->store();
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->removeParameter('id')->redirect();
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

        $pages = NavEntry::getByCondition();
        $this->meta->getTableWithStorableSorting($pages)->show();
    }
}