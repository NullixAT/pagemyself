<?php

namespace Framelix\ContactForm\PageBlocks;

use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\PageBlock;

use function rawurlencode;

/**
 * ContactForm
 */
class ContactForm extends BlockBase
{
    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'getemail':
                $pageBlock = PageBlock::getById(Request::getGet('pageBlockId'));
                if (!$pageBlock) {
                    return;
                }
                $settings = $pageBlock->pageBlockSettings;
                $mailto = 'mailto:' . ($settings['email'] ?? null) . "?";
                $mailto .= 'subject=' . rawurlencode($settings['defaultSubject'] ?? '') . "&";
                $mailto .= 'body=' . rawurlencode($settings['defaultBody'] ?? '');
                $jsCall->result = $mailto;
                break;
            case 'submit':
                $pageBlock = PageBlock::getById(Request::getGet('pageBlockId'));
                if (!$pageBlock || !\Framelix\Framelix\Utils\Email::isAvailable()) {
                    return;
                }
                $settings = $pageBlock->pageBlockSettings;
                if ($settings['email'] ?? null && ($settings['type'] ?? null) === 'form') {
                    if (BruteForceProtection::isBlocked(__CLASS__, true, 0, 60 * 5)) {
                        Response::showFormAsyncSubmitResponse();
                    }
                    BruteForceProtection::reset(__CLASS__);
                    BruteForceProtection::countUp(__CLASS__);
                    \Framelix\Framelix\Utils\Email::send(
                        $settings['defaultSubject'] ?? '__contactform_email_subject__',
                        (string)(Request::getPost('text') ?? ''),
                        $settings['email'],
                        replyTo: Request::getPost('email')
                    );
                    Toast::success('__contactform_message_sent__', -1);
                    Url::getBrowserUrl()->redirect();
                }
        }
    }

    /**
     * Get form
     * @param PageBlock $pageBlock
     * @return Form
     */
    public static function getForm(PageBlock $pageBlock): Form
    {
        $form = new Form();
        $form->id = "contactform";
        $form->submitUrl = JsCall::getCallUrl(__CLASS__, 'submit', ['pageBlockId' => $pageBlock]);

        $field = new Email();
        $field->name = "email";
        $field->label = "__contactform_email__";
        $field->required = true;
        $form->addField($field);

        $field = new Textarea();
        $field->name = "text";
        $field->label = "__contactform_message__";
        $field->minLength = 10;
        $field->required = true;
        $form->addField($field);

        return $form;
    }

    /**
     * Prepare settings for template code generator to remove sensible data
     * Should be used to remove settings like media files or non layout settings from the settings array
     * @param array $pageBlockSettings
     */
    public static function prepareTemplateSettingsForExport(array &$pageBlockSettings): void
    {
        // only store the type and skip others
        foreach ($pageBlockSettings as $key => $value) {
            if ($key !== 'type') {
                unset($pageBlockSettings[$key]);
            }
        }
    }

    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $settings = $this->pageBlock->pageBlockSettings;
        $type = ($settings['type'] ?? 'btn');
        if (!\Framelix\Framelix\Utils\Email::isAvailable()) {
            $type = 'btn';
        }
        if ($type === 'btn') {
            $getEmailUrl = JsCall::getCallUrl(
                __CLASS__,
                'getemail',
                ['pageBlockId' => $this->pageBlock]
            );
            ?>
            <div class="framelix-button framelix-button-primary contactform-submit"
                 data-icon-left="email"><?= Lang::get('__contactform_submit__') ?></div>
            <script>
              (function () {
                $('.contactform-submit').on('click', async function () {
                  window.open(await FramelixApi.callPhpMethod(<?=JsonUtils::encode($getEmailUrl)?>))
                })
              })()
            </script>
            <?php
        } else {
            $form = self::getForm($this->pageBlock);
            $form->addSubmitButton('save', '__contactform_submit__', 'save');
            $form->show();
        }
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new Select();
        $field->name = 'type';
        $field->addOption('form', '__contactform_pageblocks_contactform_type_form__');
        $field->addOption('btn', '__contactform_pageblocks_contactform_type_btn__');
        $form->addField($field);

        $field = new Email();
        $field->name = 'email';
        $form->addField($field);

        $field = new Text();
        $field->name = 'defaultSubject';
        $form->addField($field);

        $field = new Textarea();
        $field->name = 'defaultBody';
        $field->getVisibilityCondition()->equal("type", "btn");
        $form->addField($field);

        if (\Framelix\Framelix\Utils\Email::isAvailable()) {
            $field = new Html();
            $field->name = 'email_notice';
            $field->label = '';
            $field->defaultValue = Lang::get('__contactform_emailconfig_missing__');
            $form->addField($field);
        }
    }

    /**
     * Get array of settings forms
     * If more then one form is returned, it will create tabs with forms
     * @return Form[]
     */
    public function getSettingsForms(): array
    {
        $forms = parent::getSettingsForms();

        $form = new Form();
        $form->id = "main";
        $forms[] = $form;

        return $forms;
    }
}