<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class GithubLinkWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.github-link-widget';
}
