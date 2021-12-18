<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\Ace;
use Framelix\Myself\Form\Field\MediaBrowser;

/**
 * WebsiteSettings
 */
class WebsiteSettings extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,content";

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Form::isFormSubmitted('globalsettings')) {
            $form = $this->getForm();
            $instance = \Framelix\Myself\Storable\WebsiteSettings::getInstance();
            $form->setStorableValues($instance);
            $instance->store();
            Toast::success('__saved__');
            Response::showFormAsyncSubmitResponse();
        }
        $form = $this->getForm();
        $form->addSubmitButton('save', '__save__', 'save');
        $form->show();
    }

    /**
     * Get form
     * @return Form
     */
    private function getForm(): Form
    {
        $instance = \Framelix\Myself\Storable\WebsiteSettings::getInstance();
        $form = new Form();
        $form->id = "globalsettings";
        $form->submitUrl = Url::create();

        $field = new MediaBrowser();
        $field->name = 'settings[favicon]';
        $field->label = '__myself_websitesettings_favicon__';
        $field->labelDescription = '__myself_websitesettings_favicon_desc__';
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Ace();
        $field->label = '__myself_websitesettings_pagejs__';
        $field->labelDescription = '__myself_websitesettings_pagejs_desc__';
        $field->name = 'settings[pagejs]';
        $field->mode = 'javascript';
        $field->initialHidden = true;
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Ace();
        $field->label = '__myself_websitesettings_pagecss__';
        $field->labelDescription = '__myself_websitesettings_pagecss_desc__';
        $field->name = 'settings[pagecss]';
        $field->mode = 'css';
        $field->initialHidden = true;
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        return $form;
    }

}