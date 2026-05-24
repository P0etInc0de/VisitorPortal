<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

return [
    'check_in_window_hours' => (int) env('RECEPTION_CHECK_IN_WINDOW_HOURS', 48),
    'badge_preparation_window_hours' => (int) env('RECEPTION_BADGE_PREPARATION_WINDOW_HOURS', 48),
];
