<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    // Changes the name in the sidebar menu
    protected static ?string $navigationLabel = null;

    // Changes the main heading at the top of the page
    protected ?string $heading = null;

    // (Optional) Changes the browser tab title
    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('Admin-Dashboard');
    }

    public function getHeading(): string
    {
        return __('Admin-Dashboard');
    }

    public function getTitle(): string
    {
        return __('Admin-Dashboard');
    }
}
