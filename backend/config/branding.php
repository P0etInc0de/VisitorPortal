<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

return [

    /*
    |--------------------------------------------------------------------------
    | White-label Branding
    |--------------------------------------------------------------------------
    |
    | These values define the default product branding. Override them via
    | environment variables to adapt the portal for a customer or organization
    | without changing application code.
    |
    */

    'name' => env('BRANDING_NAME', 'VisitorPortal'),

    'logo_light' => env('BRANDING_LOGO_LIGHT', 'images/branding/logo-with-text-light.svg'),

    'logo_dark' => env('BRANDING_LOGO_DARK', 'images/branding/logo-with-text-dark.svg'),

    'mail_logo' => env('BRANDING_MAIL_LOGO', env('BRANDING_LOGO_LIGHT', 'images/branding/logo-with-text-light.svg')),

    'badge_logo' => env('BRANDING_BADGE_LOGO', env('BRANDING_LOGO_LIGHT', 'images/branding/logo-with-text-light.svg')),

    'badge_design' => env('BRANDING_BADGE_DESIGN', 'standard'),

    'badge_accent_color' => env('BRANDING_BADGE_ACCENT_COLOR', '#ff8a00'),

    'monitor_fallback_heading' => env('BRANDING_MONITOR_FALLBACK_HEADING', 'Welcome to VisitorPortal'),

    'monitor_fallback_subheading' => env('BRANDING_MONITOR_FALLBACK_SUBHEADING', "We're glad you're here."),

    'monitor_slide_heading' => env('BRANDING_MONITOR_SLIDE_HEADING', 'Welcome!'),

    // Automatic monitor generation is disabled by default. This is the main privacy-by-default safeguard.
    'monitor_auto_generation' => (bool) env('BRANDING_MONITOR_AUTO_GENERATION', false),

    // The default display mode intentionally uses title + first initial + last name instead of company-only.
    // Company names are not inherently less sensitive: a company name can reveal a lawyer, insolvency advisor,
    // auditor, medical provider, union representative, security contractor, or other sensitive visit context.
    'monitor_display_mode' => env('BRANDING_MONITOR_DISPLAY_MODE', 'title_first_initial_last_name'),

];
