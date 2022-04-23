<?php

namespace Framelix\PageMyself\Component;

use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ColorUtils;
use Framelix\PageMyself\Storable\ComponentBlock;
use Framelix\PageMyself\Storable\ComponentCalendarEntry;

/**
 * Calendar
 */
class Calendar extends ComponentBase
{
    /**
     * The current date
     * @var Date|null
     */
    private ?Date $date;

    /**
     * Get table for month
     * @param ComponentBlock $componentBlock
     * @param Date $date
     * @return void
     */
    public static function showTable(ComponentBlock $componentBlock, Date $date): void
    {
        $settings = $componentBlock->settings;
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
        $range = Date::rangeDays($date->dateTime->format("Y-m-01"), $date->dateTime->format("Y-m-t"));
        foreach ($range as $month) {
            if (isset($settings['entries'][$month->getDbValue()])) {
                $entries[$month->dateTime->getDayOfMonth()] = $settings['entries'][$month->getDbValue()];
            }
        }
        ?>
        <div class="calendar-month-select">
            <?php
            if ($date->getSortableValue() >= $minDate->getSortableValue()) {
                ?>
                <a href="<?= Url::getBrowserUrl()->setParameter(
                    'calendarDate',
                    $prevMonth
                )->setHash("block-" . $componentBlock) ?>" data-action="gettable"
                   data-date="<?= $prevMonth->getDbValue() ?>" class="framelix-button framelix-button-trans">«</a>
                <?php
            }
            ?>
            <strong><?= $date->dateTime->getMonthNameAndYear() ?></strong>
            <?php
            if ($date->getSortableValue() <= $maxDate->getSortableValue()) {
                ?>
                <a href="<?= Url::getBrowserUrl()->setParameter(
                    'calendarDate',
                    $nextMonth
                )->setHash("block-" . $componentBlock) ?>" data-action="gettable"
                   data-date="<?= $nextMonth->getDbValue() ?>" class="framelix-button framelix-button-trans">»</a>
                <?php
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
                    $attributes->set('data-date', $weekDate->dateTime->format('Y-m-d'));
                    if ($date->dateTime->getMonth() !== $weekDate->dateTime->getMonth()) {
                        $attributes->addClass('calendar-othermonth');
                    } else {
                        $entry = $entries[$weekDate->dateTime->getDayOfMonth()] ?? null;
                        $color = isset($entry['color']) && $entry['color'] ? $entry['color'] : null;
                        if (!$color && isset($settings['defaultCellColor']) && $settings['defaultCellColor']) {
                            $color = $settings['defaultCellColor'];
                        }
                        if ($color) {
                            $attributes->setStyle('background-color', $color);
                            $attributes->setStyle('color', ColorUtils::invertColor($color, true));
                        }
                        $info = $entry['info'] ?? null;
                        if ($info) {
                            $attributes->set('title', $info);
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
     * On api request from frontend
     * @param string $action
     * @param array|null $parameters
     * @return void
     */
    public function onApiRequest(string $action, ?array $parameters): void
    {
        $settings = $this->block->settings;
        parent::onApiRequest($action, $parameters);
        switch ($action) {
            case 'getTable':
                $date = Date::create($parameters['date']);
                $date->dateTime->setDayOfMonth(1);
                self::showTable($this->block, $date);
                break;
            case 'dateInfo':
                $date = Date::create($parameters['date']);
                if (Request::getPost('framelix-form-form')) {
                    $color = Request::getPost('predefinedColor') ?: Request::getPost('color');
                    if ($color && strlen($color) !== 7) {
                        $color = null;
                    }
                    $settings['entries'][$date->getDbValue()] = [
                        'color' => $color,
                        'info' => Request::getPost('info'),
                        'internalInfo' => Request::getPost('internalInfo'),
                    ];
                    $this->block->settings = $settings;
                    $this->block->store();
                    Toast::success('__framelix_saved__');
                    Url::getBrowserUrl()->redirect();
                }
                ?>
                <h2><?= $date->dateTime->getDayOfMonth() ?>. <?= $date->dateTime->getMonthNameAndYear() ?></h2>
                <?php
                $form = new Form();
                $form->id = "form";
                $form->submitUrl = $this->getApiRequestUrl($action, $parameters);
                $entry = $settings['entries'][$date->getDbValue()] ?? null;

                $db = Mysql::get();
                $values = $db->fetchColumn(
                    "
                    SELECT DISTINCT(`color`)
                    FROM `" . ComponentCalendarEntry::class . "`
                "
                );
                if (isset($settings['entries'])) {
                    foreach ($settings['entries'] as $row) {
                        if ($row['color']) {
                            $values[] = $row['color'];
                        }
                    }
                }
                if (isset($settings['cellColor']) && $settings['cellColor']) {
                    $values[] = $settings['cellColor'];
                }
                $values = array_unique($values);
                if ($values) {
                    $field = new Select();
                    $field->name = "predefinedColor";
                    $field->label = '__pagemyself_component_calendar_' . strtolower($field->name) . '__';
                    foreach ($values as $value) {
                        $field->addOption(
                            $value,
                            '<span class="myself-tag" style="background-color: ' . $value . '; color:' . ColorUtils::invertColor(
                                $value,
                                true
                            ) . '">' . $value . '</span>'
                        );
                    }
                    $form->addField($field);
                }

                $field = new Color();
                $field->name = "color";
                $field->label = '__pagemyself_component_calendar_' . strtolower($field->name) . '__';
                $field->defaultValue = $entry['color'] ?? null;
                $field->getVisibilityCondition()->empty('predefinedColor');
                $form->addField($field);

                $field = new Text();
                $field->name = "info";
                $field->label = '__pagemyself_component_calendar_' . strtolower($field->name) . '__';
                $field->defaultValue = $entry['info'] ?? null;
                $form->addField($field);

                $field = new Textarea();
                $field->name = "internalInfo";
                $field->label = '__pagemyself_component_calendar_' . strtolower($field->name) . '__';
                $field->defaultValue = $entry['internalInfo'] ?? null;
                $form->addField($field);

                $form->addSubmitButton();
                $form->show();
                break;
        }
    }

    /**
     * Get default settings for this block
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [];
    }

    /**
     * Show content for this block
     * @return void
     */
    public function show(): void
    {
        $this->date = Date::create(Request::getGet('calendarDate') ?? "now");
        if (!$this->date) {
            $this->date = Date::create("now");
        }
        $this->date->dateTime->setDayOfMonth(1);
        ?>
        <div class="calendar-table">
            <?php
            self::showTable($this->block, $this->date);
            ?>
        </div>
        <?php
    }

    /**
     * Add setting fields to the settings form that is displayed when the user click the settings icon
     */
    public function addSettingFields(Form $form): void
    {
        $field = new Color();
        $field->name = 'defaultCellColor';
        $field->label = '__pagemyself_component_calendar_' . strtolower($field->name) . '__';
        $form->addField($field);
    }
}