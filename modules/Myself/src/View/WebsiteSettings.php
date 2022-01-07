<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\HtmlUtils;
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
        if (Form::isFormSubmitted('meta')) {
            $form = $this->getFormMeta();
            $instance = \Framelix\Myself\Storable\WebsiteSettings::getInstance();
            $form->setStorableValues($instance);
            $instance->store();
            Toast::success('__framelix_saved__');
            Response::showFormAsyncSubmitResponse();
        }
        switch ($this->tabId) {
            case 'meta':
                $form = $this->getFormMeta();
                $form->addSubmitButton('save', '__framelix_save__', 'save');
                $form->show();
                break;
            case 'pages':
                echo '<p>' . Lang::get('__myself_websitesettings_backend__') . '</p>';
                echo '<a href="' . View::getUrl(
                        \Framelix\Myself\View\Backend\Page\Index::class
                    ) . '" class="framelix-button framelix-button-primary" target="_blank">' . Lang::get(
                        '__myself_goto_backend__'
                    ) . '</a>';
                break;
            case 'nav':
                echo '<p>' . Lang::get('__myself_websitesettings_backend__') . '</p>';
                echo '<a href="' . View::getUrl(
                        \Framelix\Myself\View\Backend\Nav\Index::class
                    ) . '" class="framelix-button framelix-button-primary" target="_blank">' . Lang::get(
                        '__myself_goto_backend__'
                    ) . '</a>';
                break;
            default:
                $tabs = new Tabs();
                $tabs->addTab('meta', '__myself_websitesettings_meta_', new self());
                $tabs->addTab('pages', '__myself_view_backend_page_index__', new self());
                $tabs->addTab('nav', '__myself_view_backend_nav_index__', new self());
                $tabs->show();
        }
    }

    /**
     * Get form
     * @return Form
     */
    private function getFormMeta(): Form
    {
        $instance = \Framelix\Myself\Storable\WebsiteSettings::getInstance();
        $form = new Form();
        $form->stickyFormButtons = true;
        $form->id = "meta";
        $form->submitUrl = Url::create();

        $field = new MediaBrowser();
        $field->name = 'settings[og_image]';
        $field->label = '__myself_websitesettings_og_image__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $field->setOnlyImages();
        $form->addField($field);

        $field = new MediaBrowser();
        $field->name = 'settings[favicon]';
        $field->label = '__myself_websitesettings_favicon__';
        $field->labelDescription = '__myself_websitesettings_favicon_desc__';
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $field->setOnlyImages();
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[author]';
        $field->label = '__myself_websitesettings_author__';
        $field->labelDescription = Lang::get('__myself_websitesettings_search_engine_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[keywords]';
        $field->label = '__myself_websitesettings_keywords__';
        $field->labelDescription = Lang::get('__myself_websitesettings_keywords_desc__') . "<br/>" . Lang::get(
                '__myself_websitesettings_search_engine_data__'
            );
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[og_site_name]';
        $field->label = '__myself_websitesettings_og_site_name__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[og_title]';
        $field->label = '__myself_websitesettings_og_title__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'settings[og_description]';
        $field->label = '__myself_websitesettings_og_description__';
        $field->labelDescription = Lang::get('__myself_websitesettings_og_data__');
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
        $form->addField($field);

        $field = new Ace();
        $field->label = HtmlUtils::escape(Lang::get('__myself_websitesettings_headhtml__'));
        $field->labelDescription = '__myself_websitesettings_headhtml_desc__';
        $field->name = 'settings[headHtml]';
        $field->mode = 'html';
        $field->initialHidden = true;
        $field->defaultValue = ArrayUtils::getValue($instance, $field->name);
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