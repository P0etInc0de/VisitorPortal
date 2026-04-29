<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    // Changes the name in the sidebar menu
    protected static ?string $navigationLabel = 'Admin-Dashboard';

    // Changes the main heading at the top of the page
    protected ?string $heading = 'Admin-Dashboard';

    // (Optional) Changes the browser tab title
    protected static ?string $title = 'Admin-Dashboard';
}
