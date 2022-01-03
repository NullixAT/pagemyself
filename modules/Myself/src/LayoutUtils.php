<?php

namespace Framelix\Myself;

use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\ArrayUtils;
use Throwable;

use function htmlentities;
use function strlen;

/**
 * LayoutUtils
 */
class LayoutUtils
{
    /**
     * Check if generally the user is allowed to use edit mode
     * This does NOT check if the page is currently running in edit mode
     * It cannot be checked by php backend, it need to be done in frontend
     * @return bool
     */
    public static function isEditAllowed(): bool
    {
        return User::hasRole('admin,content');
    }

    /**
     * Handle a throwable - Display an error message a log error correctly
     * @param Throwable $e
     * @return void
     */
    public static function handleThrowable(Throwable $e)
    {
        ?>
        <div class="framelix-alert framelix-alert-error">
            <?= Lang::get('__myself_layout_exception__') ?>
            <?
            if (self::isEditAllowed()) {
                ?>
                <div class="framelix-spacer"></div>
                <div style="color:red;"><?= $e->getMessage() ?></div>
                <pre><?= htmlentities($e->getTraceAsString()) ?></pre>
                <?
            }
            ?>
        </div>
        <?php
    }

    /**
     * Show live editable text content block
     * If not in editmode, it shows the content right away without editing features
     * @param bool $wysiwyg If true, then it will show a full featured wysiwyg editor
     * @param Storable $storable The storable to store the edited content in
     * @param string $propertyName The property name from the storable to store content in
     * @param string|null $arrayKey If to store in a array, this is the array key to store in $propertyName, example: column[0][content]
     * @param string|null $defaultValue If value of storable is empty, then use this default value instead
     * @return void
     */
    public static function showLiveEditableText(
        bool $wysiwyg,
        Storable $storable,
        string $propertyName,
        ?string $arrayKey = null,
        ?string $defaultValue = null
    ): void {
        if ($arrayKey) {
            $content = ArrayUtils::getValue($storable->{$propertyName}, $arrayKey) ?? '';
        } else {
            $content = $storable->{$propertyName} ?? '';
        }
        $class = 'myself-live-editable-text';
        if ($wysiwyg) {
            $class = 'myself-live-editable-wysiwyg';
        }
        if (strlen($content) === 0 && $defaultValue !== null) {
            $content = $defaultValue;
        }
        if (!self::isEditAllowed()) {
            echo '<div class="' . $class . '">' . $content . '</div>';
        } else {
            // show 2 containers, one with editable when in editframe, one without editable when not in edit frame
            echo '<div class="myself-hide-if-editmode ' . $class . '">' . $content . '</div>';
            echo '<div aria-hidden="true" class="myself-show-if-editmode ' . $class . '" data-id="' . $storable . '" data-property-name="' . $propertyName . '" data-array-key="' . $arrayKey . '" contenteditable="true" data-empty-text="' . Lang::get(
                    '__myself_edittext_area__'
                ) . '">' . $content . '</div>';
        }
    }
}