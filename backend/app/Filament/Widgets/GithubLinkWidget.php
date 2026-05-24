<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class GithubLinkWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.github-link-widget';
}
