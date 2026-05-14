<?php

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

];
