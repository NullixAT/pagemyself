<?php

namespace Framelix\Myself\View;

use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\View;
use Framelix\Myself\Storable\Page;

use function array_unshift;
use function preg_replace;
use function str_replace;

/**
 * ThemeSettings
 */
class ThemeSettings extends View
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
        $requestPage = Page::getById(Request::getGet('pageId'));
        $theme = $requestPage->getTheme();
        $themeBlock = $requestPage->getThemeBlock();
        $action = Request::getGet('action');
        switch ($action) {
            case 'save-settings':
                $forms = $themeBlock->getSettingsForms();
                $form = $forms[Request::getGet('formKey')];
                $themeBlock->setValuesFromSettingsForm($form);
                $theme->store();
                Toast::success('__framelix_saved__');
                Response::showFormAsyncSubmitResponse();
            case 'edit':
                $forms = $themeBlock->getSettingsForms();

                echo '<div class="myself-theme-block-edit-tabs">';
                $tabs = new Tabs();
                $tabs->id = "themeblocks";
                foreach ($forms as $key => $form) {
                    $form->submitUrl = Url::create()->setParameter('formKey', $key)->setParameter(
                        'action',
                        'save-settings'
                    );
                    $form->id = $form->id ?? $key;
                    foreach ($form->fields as $field) {
                        $keyParts = ArrayUtils::splitKeyString($field->name);
                        if ($field->label === null) {
                            $field->label = ClassUtils::getLangKey(
                                $themeBlock,
                                $field->name
                            );
                            $field->label = preg_replace("~\[(.*?)\]~", "_$1", $field->label);
                            $field->label = str_replace("_settings", "", $field->label);
                        }
                        if ($field->labelDescription === null) {
                            $langKey = ClassUtils::getLangKey(
                                $themeBlock,
                                $field->name . "_desc"
                            );
                            if (Lang::keyExist($langKey)) {
                                $field->labelDescription = Lang::get($langKey);
                            }
                        }
                        $field->defaultValue = ArrayUtils::getValue(
                                $theme,
                                $keyParts
                            ) ?? $field->defaultValue;
                        array_unshift($keyParts, "settings");
                    }
                    $label = $form->label ?? ClassUtils::getLangKey(
                            $themeBlock,
                            "form_" . $form->id
                        );
                    $form->label = $label;
                    Buffer::start();
                    foreach ($form->fields as $field) {
                        if ($field instanceof Hidden || $field instanceof Html) {
                            continue;
                        }
                        $form->addSubmitButton('save', '__framelix_save__', 'save');
                        break;
                    }
                    $themeBlock->showSettingsForm($form);
                    $content = Buffer::get();
                    $tabs->addTab($form->id, $label, $content);
                }
                $tabs->show();
                echo '</div>';
                break;
        }
    }
}