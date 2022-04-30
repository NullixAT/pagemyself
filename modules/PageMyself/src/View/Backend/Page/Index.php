<?php

namespace Framelix\PageMyself\View\Backend\Page;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Storable\Page;

/**
 * Tab view
 */
class Index extends View
{

    /**
     * The storable
     * @var Page
     */
    private Page $storable;

    /**
     * The storable meta
     * @var \Framelix\PageMyself\StorableMeta\Page
     */
    private \Framelix\PageMyself\StorableMeta\Page $meta;


    /**
     * On request
     */
    public function onRequest(): void
    {
        // just to create default if not yet exist
        Page::getDefault();
        $this->storable = Page::getByIdOrNew(Request::getGet('id'));

        $this->meta = new \Framelix\PageMyself\StorableMeta\Page($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            $this->storable->url = (string)$this->storable->url;
            $this->storable->store();
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

        $pages = Page::getByCondition();
        $this->meta->getTableWithStorableSorting($pages)->show();
        ?>
        <script>
          (async function () {

            function updateUrl () {
              let inputValue = FramelixStringUtils.slugify(urlField.getValue().toLowerCase(), false, false, /[^a-z0-9\-_\/]/i).replace(/^\/+|\/+$/g, '')
              urlField.setValue(inputValue)
            }

            const form = FramelixForm.getById('<?=$form->id?>')
            const storableId = <?=JsonUtils::encode($this->storable)?>;
            await form.rendered
            const titleField = form.fields['title']
            const urlField = form.fields['url']
            if (!storableId) {
              titleField.container.on('change', function () {
                if (!urlField.getValue().length) urlField.setValue(FramelixStringUtils.slugify(titleField.getValue().toLowerCase()), true)
              })
            }
            urlField.container.on('input ' + FramelixFormField.EVENT_CHANGE, function () {
              updateUrl()
            })
            updateUrl()
          })()
        </script>
        <?php
    }
}