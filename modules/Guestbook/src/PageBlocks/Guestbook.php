<?php

namespace Framelix\Guestbook\PageBlocks;

use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\Framelix\Url;
use Framelix\Guestbook\Storable\Entry;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\PageBlock;

use function htmlentities;

/**
 * Guestbook
 */
class Guestbook extends BlockBase
{
    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'showhide':
                $entry = Entry::getById(Request::getGet('id'));
                if ($entry) {
                    $entry->flagValidated = !$entry->flagValidated;
                    $entry->store();
                }
                break;
            case 'delete':
                $entry = Entry::getById(Request::getGet('id'));
                $entry?->delete();
                break;
            case 'save':
                $pageBlock = PageBlock::getById(Request::getGet('pageBlockId'));
                if (!$pageBlock) {
                    return;
                }
                $settings = $pageBlock->pageBlockSettings;
                $form = self::getForm($pageBlock);
                $form->validate(true);
                if (BruteForceProtection::isBlocked(__CLASS__, true, 0, 60 * 10)) {
                    Response::showFormAsyncSubmitResponse();
                }
                BruteForceProtection::reset(__CLASS__);
                BruteForceProtection::countUp(__CLASS__);
                $entry = new Entry();
                $form->setStorableValues($entry);
                $entry->flagValidated = isset($settings['validated']) && $settings['validated'];
                $entry->store();
                if (\Framelix\Framelix\Utils\Email::isAvailable() && ($settings['emailNewEntry'] ?? null)) {
                    $url = Url::getBrowserUrl()->getUrlAsString();
                    \Framelix\Framelix\Utils\Email::send(
                        '__guestbook_email_newmessage_subject__',
                        Lang::get(
                            '__guestbook_email_newmessage_text__',
                            ["url" => '<a href="' . $url . '">' . $url . '</a>']
                        ),
                        $settings['emailNewEntry']
                    );
                }
                Toast::success($entry->flagValidated ? '__guestbook_saved__' : '__guestbook_saved_draft__', -1);
                Url::getBrowserUrl()->redirect();
        }
    }

    /**
     * Get form
     * @param PageBlock $pageBlock
     * @return Form
     */
    public static function getForm(PageBlock $pageBlock): Form
    {
        $settings = $pageBlock->pageBlockSettings;
        $form = new Form();
        $form->id = "guestbook";
        $form->submitUrl = JsCall::getCallUrl(__CLASS__, 'save', ['pageBlockId' => $pageBlock]);

        if ($settings['email'] ?? false) {
            $field = new Email();
            $field->name = "email";
            $field->label = "__guestbook_email__";
            $field->required = true;
            $form->addField($field);
        }

        if ($settings['name'] ?? false) {
            $field = new Text();
            $field->name = "name";
            $field->label = "__guestbook_name__";
            $field->required = true;
            $form->addField($field);
        }

        $field = new Textarea();
        $field->name = "text";
        $field->label = "__guestbook_message__";
        $field->minLength = 10;
        $field->required = true;
        $form->addField($field);

        return $form;
    }

    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $condition = 'flagValidated = {0}';
        if (LayoutUtils::isEditAllowed()) {
            $condition = "1";
        }
        $entries = Entry::getByCondition($condition, [true], "-id");
        foreach ($entries as $entry) {
            ?>
            <div class="guestbook-pageblocks-guestbook-entry">
                <div class="guestbook-pageblocks-guestbook-entry-time"><?= $entry->createTime->getRawTextString(
                    ) ?></div>
                <?
                if ($entry->email && LayoutUtils::isEditAllowed()) {
                    ?>
                    <div class="guestbook-pageblocks-guestbook-entry-email myself-show-if-editmode"
                         title="__guestbook_email_hidden__">
                        <?= htmlentities($entry->email) ?>
                    </div>
                    <?
                }
                if ($entry->name) {
                    ?>
                    <div class="guestbook-pageblocks-guestbook-entry-name">
                        <?
                        LayoutUtils::showLiveEditableText(false, $entry, "name");
                        ?>
                    </div>
                    <?
                }
                ?>
                <div class="guestbook-pageblocks-guestbook-entry-message">
                    <?
                    LayoutUtils::showLiveEditableText(false, $entry, "text");
                    ?>
                </div>
                <?

                if (LayoutUtils::isEditAllowed()) {
                    $deleteUrl = JsCall::getCallUrl(__CLASS__, 'delete', ['id' => $entry]);
                    $showHideUrl = JsCall::getCallUrl(__CLASS__, 'showhide', ['id' => $entry]);
                    ?>
                    <div class="guestbook-pageblocks-guestbook-buttons myself-show-if-editmode">
                        <button class="framelix-button framelix-button-error framelix-button-small"
                                data-icon-left="clear" data-delete-url="<?= $deleteUrl ?>"><?= Lang::get(
                                '__guestbook_delete__'
                            ) ?></button>
                        <?
                        if (!$entry->flagValidated) {
                            ?>
                            <button class="framelix-button framelix-button-success framelix-button-small"
                                    data-icon-left="check" data-showhide-url="<?= $showHideUrl ?>"><?= Lang::get(
                                    '__guestbook_show__'
                                ) ?></button>
                            <?
                        } else {
                            ?>
                            <button class="framelix-button framelix-button-small"
                                    data-icon-left="hide_source" data-showhide-url="<?= $showHideUrl ?>"><?= Lang::get(
                                    '__guestbook_hide__'
                                ) ?></button>
                            <?
                        }
                        ?>
                    </div>
                    <?
                }
                ?>
            </div>
            <?php
        }
        $form = self::getForm($this->pageBlock);
        $form->addSubmitButton('save', '__guestbook_save_entry__', 'save');
        $form->show();
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

        $field = new Toggle();
        $field->name = 'pageBlockSettings[email]';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'pageBlockSettings[name]';
        $form->addField($field);

        if (\Framelix\Framelix\Utils\Email::isAvailable()) {
            $field = new Email();
            $field->name = 'pageBlockSettings[emailNewEntry]';
            $form->addField($field);
        } else {
            $field = new Html();
            $field->name = 'email_notice';
            $field->defaultValue = Lang::get('__guestbook_emailconfig_missing__');
            $form->addField($field);
        }

        return $forms;
    }
}