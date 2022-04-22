<?php

namespace Framelix\PageMyself\View\Backend;

use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use Framelix\PageMyself\Form\Field\MediaBrowser;

/**
 * Website settings
 */
class WebsiteSettings extends View
{

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getPost('framelix-form-settings')) {
            $form = $this->getForm();
            foreach ($form->fields as $field) {
                \Framelix\PageMyself\Storable\WebsiteSettings::set($field->name, $field->getConvertedSubmittedValue());
            }
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show the page content
     */
    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton();
        $form->show();
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "settings";

        $field = new MediaBrowser();
        $field->name = 'websitesetting_og_image';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->labelDescription = Lang::get('__pagemyself_websitesettings_og_data__');
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $field->setOnlyImages();
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'websitesetting_favicon';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Text();
        $field->name = 'websitesetting_author';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->labelDescription = Lang::get('__pagemyself_websitesettings_search_engine_data__');
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'websitesetting_keywords';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->labelDescription = Lang::get('__pagemyself_websitesettings_search_engine_data__');
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'websitesetting_og_site_name';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->labelDescription = Lang::get('__pagemyself_websitesettings_og_data__');
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'websitesetting_og_title';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->labelDescription = Lang::get('__pagemyself_websitesettings_og_data__');
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'websitesetting_og_description';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->labelDescription = Lang::get('__pagemyself_websitesettings_og_data__');
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $form->addField($field);

        $field = new Textarea();
        $field->name = 'websitesetting_headhtml';
        $field->label = '__pagemyself_' . $field->name . '__';
        $field->labelDescription = '__pagemyself_websitesettings_headhtml_desc__';
        $field->defaultValue = \Framelix\PageMyself\Storable\WebsiteSettings::get($field->name);
        $form->addField($field);

        return $form;
    }
}