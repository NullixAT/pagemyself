<?php

namespace Framelix\Myself\View\Backend\Tag;

use Exception;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Tag;

use function in_array;

/**
 * Edit/Create tag
 */
class Edit extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,tags";

    /**
     * The storable
     * @var Tag
     */
    private Tag $storable;

    /**
     * The storable meta
     * @var \Framelix\Myself\StorableMeta\Tag
     */
    private \Framelix\Myself\StorableMeta\Tag $meta;

    /**
     * Category
     * @var int
     */
    private int $category;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->category = (int)Request::getGet('category');
        $this->storable = Tag::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
            $this->storable->category = $this->category;
        } else {
            $this->category = $this->storable->category;
        }
        if (!in_array($this->category, Tag::$categories)) {
            throw new Exception("Missing category");
        }
        $this->pageTitle = "__myself_tag_category_{$this->category}__";
        $this->meta = new \Framelix\Myself\StorableMeta\Tag($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            if ($this->storable->sort === null) {
                $this->storable->sort = 0;
            }
            $this->storable->store();
            Toast::success('__saved__');
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

        $this->meta->getTableWithStorableSorting(
            Tag::getByCondition(
                'category = {0}',
                [$this->category],
                "+sort"
            )
        )->show();
    }
}