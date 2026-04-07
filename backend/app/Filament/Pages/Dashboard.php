<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    // Changes the name in the sidebar menu
    protected static ?string $navigationLabel = 'Admin Panel';

    // Changes the main heading at the top of the page
    protected ?string $heading = 'Admin Panel';

    // (Optional) Changes the browser tab title
    protected static ?string $title = 'Admin Panel';
}
