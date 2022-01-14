<?php

namespace Framelix\Calendar\PageBlocks;

use Framelix\Calendar\Storable\Entry;
use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ColorUtils;
use Framelix\Myself\LayoutUtils;
use Framelix\Myself\PageBlocks\BlockBase;
use Framelix\Myself\Storable\PageBlock;

/**
 * Calendar
 */
class Calendar extends BlockBase
{
    /**
     * The current date
     * @var Date|null
     */
    private ?Date $date;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        $pageBlock = PageBlock::getById(Request::getGet('pageBlockId'));
        if (!$pageBlock) {
            return;
        }
        $settings = $pageBlock->pageBlockSettings;
        $date = Date::create(Request::getGet('date'));
        switch ($jsCall->action) {
            case 'gettable':
                $date->dateTime->setDayOfMonth(1);
                self::showTable($pageBlock, $date);
                break;
            case 'save':
                if ($settings['global'] ?? null) {
                    $entry = Entry::getByConditionOne('date = {0}', [$date]);
                    if (!$entry) {
                        $entry = new Entry();
                        $entry->date = $date;
                    }
                    $entry->color = Request::getPost('color');
                    $entry->info = Request::getPost('info');
                    $entry->internalInfo = Request::getPost('internalInfo');
                    $entry->store();
                } else {
                    $settings['entries'][$date->getDbValue()] = [
                        'color' => Request::getPost('color'),
                        'info' => Request::getPost('info'),
                        'internalInfo' => Request::getPost('internalInfo'),
                    ];
                    $pageBlock->pageBlockSettings = $settings;
                    $pageBlock->store();
                }
                Toast::success('__framelix_saved__');
                Url::getBrowserUrl()->redirect();
            case 'edit':
                ?>
                <h2><?= $date->dateTime->getDayOfMonth() ?>. <?= $date->dateTime->getMonthNameAndYear() ?></h2>
                <?php
                $form = new Form();
                $form->id = "editentry";
                $form->submitUrl = JsCall::getCallUrl(
                    __CLASS__,
                    'save',
                    ['date' => $date, 'pageBlockId' => $pageBlock]
                );
                if ($settings['global'] ?? null) {
                    $object = Entry::getByConditionOne('date = {0}', [$date]);
                    $entry = [
                        'color' => $object->color ?? null,
                        'info' => $object->info ?? null,
                        'internalInfo' => $object->internalInfo ?? null,
                    ];
                } else {
                    $entry = $settings['entries'][$date->getDbValue()] ?? null;
                }

                $field = new Color();
                $field->name = "color";
                $field->label = '__calendar_pageblocks_calendar_cellcolor_day__';
                $field->defaultValue = $entry['color'] ?? null;
                $form->addField($field);

                $field = new Text();
                $field->name = "info";
                $field->label = '__calendar_pageblocks_calendar_cellinfo__';
                $field->defaultValue = $entry['info'] ?? null;
                $form->addField($field);

                $field = new Textarea();
                $field->name = "internalInfo";
                $field->label = '__calendar_pageblocks_calendar_internalinfo__';
                $field->defaultValue = $entry['internalInfo'] ?? null;
                $form->addField($field);

                $form->addSubmitButton('save', '__framelix_save__', 'save');
                $form->show();
                break;
        }
    }

    /**
     * Get table for month
     * @param PageBlock $pageBlock
     * @param Date $date
     * @return void
     */
    public static function showTable(PageBlock $pageBlock, Date $date): void
    {
        $settings = $pageBlock->pageBlockSettings;
        $minDate = Date::create($settings['minDate'] ?? 'now - 10 years');
        $maxDate = Date::create($settings['maxDate'] ?? 'now + 10 years');
        if ($date->getSortableValue() < $minDate->getSortableValue()) {
            $date = $minDate;
        }
        if ($date->getSortableValue() > $maxDate->getSortableValue()) {
            $date = $maxDate;
        }
        $prevMonth = $date->clone();
        $prevMonth->dateTime->modify("-1 month");
        $nextMonth = $date->clone();
        $nextMonth->dateTime->modify("+1 month");
        $entries = [];
        if ($settings['global'] ?? null) {
            $objects = Entry::getByCondition(
                'date BETWEEN {0} AND {1}',
                [$date->dateTime->format("Y-m-01"), $date->dateTime->format("Y-m-t")]
            );
            foreach ($objects as $object) {
                $entries[$object->date->dateTime->getDayOfMonth()] = [
                    'color' => $object->color,
                    'info' => $object->info,
                ];
            }
        } else {
            $range = Date::rangeDays($date->dateTime->format("Y-m-01"), $date->dateTime->format("Y-m-t"));
            foreach ($range as $month) {
                if (isset($settings['entries'][$month->getDbValue()])) {
                    $entries[$month->dateTime->getDayOfMonth()] = $settings['entries'][$month->getDbValue()];
                }
            }
        }
        ?>
        <div class="calendar-pageblocks-calendar-month-select">
            <?
            if ($date->getSortableValue() >= $minDate->getSortableValue()) {
                ?>
                <a href="<?= Url::getBrowserUrl()->setParameter(
                    'calendarDate',
                    $prevMonth
                )->setHash("pageblock-" . $pageBlock) ?>" data-jscall="<?= JsCall::getCallUrl(
                    __CLASS__,
                    'gettable',
                    ['pageBlockId' => $pageBlock, 'date' => $prevMonth]
                ) ?>" class="framelix-button framelix-button-trans">«</a>
                <?
            }
            ?>
            <strong><?= $date->dateTime->getMonthNameAndYear() ?></strong>
            <?
            if ($date->getSortableValue() <= $maxDate->getSortableValue()) {
                ?>
                <a href="<?= Url::getBrowserUrl()->setParameter(
                    'calendarDate',
                    $nextMonth
                )->setHash("pageblock-" . $pageBlock) ?>" data-jscall="<?= JsCall::getCallUrl(
                    __CLASS__,
                    'gettable',
                    ['pageBlockId' => $pageBlock, 'date' => $nextMonth]
                ) ?>" class="framelix-button framelix-button-trans">»</a>
                <?
            }
            ?>
        </div>
        <table>
            <thead>
            <tr>
                <?php
                for ($i = 1; $i <= 7; $i++) {
                    echo '<th>' . Lang::get('__framelix_dayshort_' . $i . '__') . '</th>';
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            $week = -1;
            while ($week <= 6) {
                $week++;
                $weekStart = $week > 0 ? DateTime::create(
                    $date->getDbValue() . " + $week weeks monday this week"
                ) : DateTime::create($date->getDbValue() . " monday this week");
                if ((int)$weekStart->format("Ym") > (int)$date->dateTime->format("Ym")) {
                    break;
                }
                echo '<tr>';
                for ($i = 1; $i <= 7; $i++) {
                    $addDays = $i - 1;
                    $weekDate = Date::create($weekStart->format("Y-m-d"));
                    if ($addDays > 0) {
                        $weekDate->dateTime->modify("+ $addDays days");
                    }
                    $html = (int)$weekDate->dateTime->format("d");
                    $attributes = new HtmlAttributes();
                    if ($date->dateTime->getMonth() !== $weekDate->dateTime->getMonth()) {
                        $attributes->addClass('calendar-pageblocks-calendar-othermonth');
                    } else {
                        $entry = $entries[$weekDate->dateTime->getDayOfMonth()] ?? null;
                        $color = $entry['color'] ?? $settings['cellColor'] ?? null;
                        if ($color) {
                            $attributes->setStyle('background-color', $color);
                            $attributes->setStyle('color', ColorUtils::invertColor($color, true));
                        }
                        $info = $entry['info'] ?? null;
                        if ($info) {
                            $attributes->set('title', $info);
                        }
                        if (LayoutUtils::isEditAllowed()) {
                            $attributes->set(
                                'data-modal',
                                JsCall::getCallUrl(
                                    __CLASS__,
                                    'edit',
                                    ['date' => $weekDate, 'pageBlockId' => $pageBlock]
                                )
                            );
                        }
                    }
                    echo '<td ' . $attributes . '>' . $html . '</td>';
                }
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Prepare settings for template code generator to remove sensible data
     * Should be used to remove settings like media files or non layout settings from the settings array
     * @param array $pageBlockSettings
     */
    public static function prepareTemplateSettingsForExport(array &$pageBlockSettings): void
    {
        // calendar does not have any settings that should be exported
        foreach ($pageBlockSettings as $key => $value) {
            unset($pageBlockSettings[$key]);
        }
    }

    /**
     * Show content for this block
     * @return void
     */
    public function showContent(): void
    {
        $this->date = Date::create(Request::getGet('calendarDate') ?? "now");
        if (!$this->date) {
            $this->date = Date::create("now");
        }
        $this->date->dateTime->setDayOfMonth(1);
        ?>
        <div class="calendar-pageblocks-calendar-table">
            <?
            self::showTable($this->pageBlock, $this->date);
            ?>
        </div>
        <?php
    }

    /**
     * Add settings fields to column settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
        $field = new Html();
        $field->name = 'info1';
        $form->addField($field);

        $field = new Toggle();
        $field->name = 'global';
        $form->addField($field);

        $field = new Select();
        $field->name = 'minDate';
        $field->searchable = true;
        for ($i = 0; $i <= 120; $i++) {
            $date = Date::create("now");
            $date->dateTime->setDayOfMonth(1);
            if ($i) {
                $date->dateTime->modify("-$i month");
            }
            $field->addOption($date->getDbValue(), $date->dateTime->getMonthNameAndYear());
        }
        $form->addField($field);

        $field = new Select();
        $field->name = 'maxDate';
        $field->searchable = true;
        for ($i = 0; $i <= 120; $i++) {
            $date = Date::create("now");
            $date->dateTime->setDayOfMonth(1);
            if ($i) {
                $date->dateTime->modify("+$i month");
            }
            $field->addOption($date->getDbValue(), $date->dateTime->getMonthNameAndYear());
        }
        $form->addField($field);

        $field = new Color();
        $field->name = 'cellColor';
        $form->addField($field);
    }
}