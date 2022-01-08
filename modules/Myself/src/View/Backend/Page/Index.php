<?php

namespace Framelix\Myself\View\Backend\Page;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Page;

/**
 * Tab view
 */
class Index extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,nav";

    /**
     * The storable
     * @var Page
     */
    private Page $storable;

    /**
     * The storable meta
     * @var \Framelix\Myself\StorableMeta\Page
     */
    private \Framelix\Myself\StorableMeta\Page $meta;


    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = Page::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
            $this->storable->lang = Lang::$lang;
        }
        $this->meta = new \Framelix\Myself\StorableMeta\Page($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
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
        ?>
        <div class="framelix-spacer-x4"></div>
        <?php
        $pages = Page::getByCondition();
        $this->meta->getTable($pages)->show();
        ?>
        <script>
          (async function () {

            function updateUrl () {
              let inputValue = FramelixStringUtils.slugify(urlField.getValue().toLowerCase(), false, false, /[^a-z0-9\-_\/]/i).replace(/^\/+|\/+$/g, '')
              urlField.setValue(inputValue)
              urlField.container.find('.framelix-form-field-label-description').html(FramelixLang.get('__myself_storable_page_url_label_desc__', [FramelixConfig.applicationUrl + '/' + inputValue]))
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