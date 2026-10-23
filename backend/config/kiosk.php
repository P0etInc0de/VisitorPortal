<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

return [
    'auto_reset_seconds' => (int) env('KIOSK_AUTO_RESET_SECONDS', 45),
    'search_result_limit' => (int) env('KIOSK_SEARCH_RESULT_LIMIT', 15),
    'touch_last_seen_seconds' => (int) env('KIOSK_TOUCH_LAST_SEEN_SECONDS', 60),
];
