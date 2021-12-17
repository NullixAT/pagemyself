<?php

namespace Framelix\Myself\View\Backend\Nav;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Nav;

/**
 * Edit/Create page
 */
class Edit extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,nav";

    /**
     * The storable
     * @var Nav
     */
    private Nav $storable;

    /**
     * The storable meta
     * @var \Framelix\Myself\StorableMeta\Nav
     */
    private \Framelix\Myself\StorableMeta\Nav $meta;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = Nav::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
            $this->storable->target = '_self';
        }
        $this->meta = new \Framelix\Myself\StorableMeta\Nav($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
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
        ?>
        <script>
          (async function () {
            const form = FramelixForm.getById('<?=$form->id?>')
            const storableId = <?=JsonUtils::encode($this->storable)?>;
            await form.rendered
            if (!storableId) {
              const title = form.fields['title']
              title.container.on('change', function () {
                const url = form.fields['url']
                if (!url.getValue().length) url.setValue(FramelixStringUtils.slugify(title.getValue().toLowerCase()))
              })
            }
          })()
        </script>
        <?php
    }
}