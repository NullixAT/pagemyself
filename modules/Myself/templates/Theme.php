<?php

namespace Framelix\__MODULE__\Themes;

use Framelix\Framelix\Form\Form;
use Framelix\Myself\Themes\ThemeBase;
use Framelix\Myself\View\Index;

/**
 * __THEMENAME__
 */
class __THEMENAME__ extends ThemeBase
{
    /**
     * Show the theme layout
     * @param Index $view The view where this theme is displayed in
     * @return void
     */
    public function showLayout(Index $view): void
    {
        $this->showUserDefinedLayout();
    }


    /**
     * Add settings fields to theme settings form
     * Name of field is settings key
     * @param Form $form
     */
    public function addSettingsFields(Form $form): void
    {
    }
}