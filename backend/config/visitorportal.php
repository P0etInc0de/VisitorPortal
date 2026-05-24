<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

return [

    'easter_egg' => [
        'enabled' => env('VISITORPORTAL_EASTER_EGG', env('APP_ENV', 'production') === 'local'),
        'show_in_production' => env('VISITORPORTAL_EASTER_EGG_PRODUCTION', false),
    ],

];
