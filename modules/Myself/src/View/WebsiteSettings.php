<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Form\Field\Ace;
use Framelix\Myself\Form\Field\MediaBrowser;
use Framelix\Myself\Storable\Page;
use Framelix\Myself\Themes\ThemeBase;

use function file_exists;
use function strtolower;

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
     * The current page
     * @var Page
     */
    private Page $page;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'set-theme':
                if ($jsCall->parameters['type'] === 'all') {
                    $pages = Page::getByCondition();
                } else {
                    $pages = Page::getByIds([Request::getGet('pageId')]);
                }
                foreach ($pages as $page) {
                    $page->themeClass = $jsCall->parameters['themeClass'];
                    $page->store();
                }
                Toast::success('__framelix_saved__');
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->page = Page::getById(Request::getGet('pageId'));
        if (Form::isFormSubmitted('themesettings')) {
            $themeSettings = $this->page->getThemeSettings();
            $form = $this->getFormThemeSettings();
            $form->setStorableValues($themeSettings);
            $themeSettings->store();
            Toast::success('__framelix_saved__');
            Response::showFormAsyncSubmitResponse();
        }
        if (Form::isFormSubmitted('meta')) {
            $form = $this->getFormMeta();
            $instance = \Framelix\Myself\Storable\WebsiteSettings::getInstance();
            $form->setStorableValues($instance);
            $instance->store();
            Toast::success('__framelix_saved__');
            Response::showFormAsyncSubmitResponse();
        }
        switch ($this->tabId) {
            case 'theme':
                echo '<div class="framelix-alert">'
                    . Lang::get(
                        '__myself_websitesettings_theme_new_themes__',
                        [
                            '<a href="' . View::getUrl(
                                \Framelix\Myself\View\Backend\Modules\Index::class
                            ) . '" target="_blank">' . Lang::get(
                                '__myself_websitesettings_theme_new_themes_modulestore__'
                            ) . '</a>',
                            '<a href="https://pagemyself.com/docs/main/dev/basics" target="_blank">' . Lang::get(
                                '__myself_websitesettings_theme_new_themes_develop__'
                            ) . '</a>'
                        ]
                    )
                    . '</div>';
                echo '<div class="theme-chooser">';
                $themeClasses = ThemeBase::getAllClasses();
                foreach ($themeClasses as $themeClass) {
                    $themeModule = ClassUtils::getModuleForClass($themeClass);
                    $themeModuleLower = strtolower($themeModule);
                    $themeName = strtolower(ClassUtils::getClassBaseName($themeClass));
                    $themeFolder = FileUtils::getModuleRootPath($themeModule) . "/public/themes/" . $themeName;
                    $screenshotFile = $themeFolder . "/screenshot.png";
                    if (!file_exists($screenshotFile)) {
                        continue;
                    }
                    ?>
                    <div class="theme-chooser-theme" data-theme-class="<?= $themeClass ?>" tabindex="0">
                        <div class="theme-chooser-title">
                            <?= Lang::get('__' . $themeModuleLower . '_theme_' . $themeName . '_title__') ?>
                        </div>
                        <div class="theme-chooser-description">
                            <?= Lang::get('__' . $themeModuleLower . '_theme_' . $themeName . '_description__') ?>
                        </div>
                        <div class="theme-chooser-screenshot"
                             style="background-image: url(<?= Url::getUrlToFile($screenshotFile) ?>)"></div>
                        <div class="theme-chooser-use">
                            <button class="framelix-button framelix-button-primary" data-icon-left="check"
                                    data-type="current"><?= Lang::get(
                                    '__myself_websitesettings_theme_usethis__'
                                ) ?></button>
                            <button class="framelix-button framelix-button-warning"
                                    data-icon-left="check" data-type="all"><?= Lang::get(
                                    '__myself_websitesettings_theme_useall__'
                                ) ?></button>
                        </div>
                    </div>
                    <?php
                }
                echo '</div>';
                ?>
                <script>
                  $('.theme-chooser').on('click', '.theme-chooser-use button', async function () {
                    if (await FramelixModal.confirm('__framelix_sure__').confirmed) {
                      await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                          __CLASS__,
                          'set-theme',
                          ['pageId' => $this->page]
                      )?>', {
                        'type': $(this).attr('data-type'),
                        'themeClass': $(this).closest('.theme-chooser-theme').attr('data-theme-class')
                      })
                      location.reload()
                    }
                  })
                </script>
                <?php
                break;
            case 'themesettings':
                $form = $this->getFormThemeSettings();
                $form->addSubmitButton('save', '__framelix_save__', 'save');
                $form->show();
                ?>
                <script>
                  (async function () {
                    const form = FramelixForm.getById('<?=$form->id?>')
                    await form.rendered

                    function addFonts () {
                      for (let key in Myself.defaultFonts) {
                        const row = Myself.defaultFonts[key]
                        fieldFontSelect.addOption(row.name, key + ' | <span style="font-family: ' + row.name + '">Lorem ipsum dolor sit amet, consetetur sadipscing elitr</span>')
                      }
                      for (let key in Myself.customFonts) {
                        const row = Myself.customFonts[key]
                        fieldFontSelect.addOption(row.name, row.name + ' | <span style="font-family: ' + row.name + '">Lorem ipsum dolor sit amet, consetetur sadipscing elitr</span>')
                      }
                      fieldFontSelect.setValue(fieldFontSelect.defaultValue)
                    }

                    /** @type {FramelixFormFieldSelect} */
                    const fieldFontSelect = form.fields['settings[defaultFont]']
                    /** @type {FramelixFormFieldTextarea} */
                    const fieldFontUrls = form.fields['settings[fontUrls]']
                    Myself.parseCustomFonts(fieldFontUrls.getValue())
                    addFonts()
                    fieldFontUrls.container.on('paste', async function () {
                      await Framelix.wait(1)
                      Myself.parseCustomFonts(fieldFontUrls.getValue())
                      let newContent = ''
                      for (let key in Myself.customFonts) {
                        newContent += Myself.customFonts[key].url + '\n'
                      }
                      fieldFontUrls.setValue(newContent.trim(), true)
                      addFonts()
                    })
                  })()
                </script>
                <?php
                break;
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
                $tabs->id = "website-settings";
                $tabs->addTab('theme', '__myself_websitesettings_theme__', new self(), $_GET);
                $tabs->addTab('themesettings', '__myself_websitesettings_themesettings__', new self(), $_GET);
                $tabs->addTab('meta', '__myself_websitesettings_meta_', new self(), $_GET);
                $tabs->addTab('pages', '__myself_view_backend_page_index__', new self(), $_GET);
                $tabs->addTab('nav', '__myself_view_backend_nav_index__', new self(), $_GET);
                $tabs->show();
        }
    }

    /**
     * Get form theme
     * @return Form
     */
    private function getFormThemeSettings(): Form
    {
        $themeSettings = $this->page->getThemeSettings();
        $themeBlock = $this->page->getThemeBlock();

        $form = new Form();
        $form->id = "themesettings";
        $form->stickyFormButtons = true;

        $field = new Textarea();
        $field->name = "fontUrls";
        $field->label = Lang::get(
            '__myself_themes_fonturls__',
            ['<a href="https://fonts.google.com" target="_blank" rel="nofollow">fonts.google.com</a>']
        );
        $field->labelDescription = Lang::get(
                '__myself_themes_fonturls_desc__'
            ) . '<br/><a href="https://www.youtube.com/watch?v=gHkSNWZ1UgM&list=PLFckrKcNoDynWKpyM-_Zc6r-UjLA-BiX8&index=3" target="_blank">» ' . Lang::get(
                '__myself_themes_fonturls_video__'
            ) . '</a>';
        $form->addField($field);

        $field = new Select();
        $field->name = "defaultFont";
        $field->label = '__myself_themes_defaultfont__';
        $field->labelDescription = '__myself_themes_defaultfont_desc__';
        $form->addField($field);

        $themeBlock->addSettingsFields($form);

        $fields = $form->fields;
        // unset because we update field names
        $form->fields = [];
        foreach ($fields as $field) {
            if ($field->label === null) {
                $field->label = ClassUtils::getLangKey(
                    $themeBlock,
                    $field->name
                );
                $field->labelDescription = ClassUtils::getLangKey(
                    $themeBlock,
                    $field->name . "_desc"
                );
                if (!Lang::keyExist($field->labelDescription)) {
                    $field->labelDescription = null;
                }
            }
            $field->defaultValue = $themeSettings->settings[$field->name] ?? null;
            $field->name = "settings[" . $field->name . "]";
            // re-add with new field name
            $form->addField($field);
        }

        return $form;
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