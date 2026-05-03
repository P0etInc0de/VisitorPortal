<style>
    /*
       Admin panel border stability.

       Filament renders many visible borders as Tailwind rings using box-shadow
       variables such as --tw-ring-shadow and --tw-ring-color. In Firefox, these
       1px box-shadow/ring based borders can render inconsistently during zoom,
       especially together with border-radius.

       For the affected admin panel surfaces and controls we use real CSS borders
       in all themes. This keeps visible edges stable while keeping the override
       limited to the Filament admin panel theme partial.
    */

    /*
       Light theme border fallback.
    */
    .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-section:not(.fi-section-not-contained).fi-aside > .fi-section-content-ctn,
    .fi-dropdown-panel,
    .fi-modal-window {
        border: 2px solid rgb(209 213 219 / 1) !important;
        box-sizing: border-box;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    .fi-input-wrp {
        border: 2px solid rgb(209 213 219 / 1) !important;
        box-sizing: border-box;
        overflow: hidden;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    .fi-input-wrp:not(.fi-disabled):not(:has(.fi-ac-action:focus)):focus-within {
        border-color: rgb(245 158 11 / 1) !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    .fi-input-wrp.fi-invalid {
        border-color: rgb(239 68 68 / 1) !important;
    }

    .fi-input-wrp .fi-input,
    .fi-input-wrp .fi-select-input,
    .fi-input-wrp .fi-textarea {
        background-color: transparent !important;
    }

    /*
       Dark theme border fallback.
    */
    html.dark .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    html[data-theme="dark"] .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    html.dark .fi-section:not(.fi-section-not-contained).fi-aside > .fi-section-content-ctn,
    html[data-theme="dark"] .fi-section:not(.fi-section-not-contained).fi-aside > .fi-section-content-ctn,
    html.dark .fi-dropdown-panel,
    html[data-theme="dark"] .fi-dropdown-panel,
    html.dark .fi-modal-window,
    html[data-theme="dark"] .fi-modal-window {
        border-color: rgb(64 64 64 / 1) !important;
    }

    html.dark .fi-input-wrp,
    html[data-theme="dark"] .fi-input-wrp {
        border-color: rgb(82 82 91 / 1) !important;
    }

    html.dark .fi-input-wrp:not(.fi-disabled):not(:has(.fi-ac-action:focus)):focus-within,
    html[data-theme="dark"] .fi-input-wrp:not(.fi-disabled):not(:has(.fi-ac-action:focus)):focus-within {
        border-color: rgb(245 158 11 / 1) !important;
    }

    /*
       True Black theme colors.
    */
    html[data-theme="true-black"] {
        color-scheme: dark;
    }

    html[data-theme="true-black"] body,
    html[data-theme="true-black"] .fi-layout,
    html[data-theme="true-black"] .fi-main,
    html[data-theme="true-black"] .fi-sidebar,
    html[data-theme="true-black"] .fi-topbar,
    html[data-theme="true-black"] .fi-header {
        background-color: #000 !important;
    }

    html[data-theme="true-black"] .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    html[data-theme="true-black"] .fi-section:not(.fi-section-not-contained).fi-aside > .fi-section-content-ctn,
    html[data-theme="true-black"] .fi-dropdown-panel,
    html[data-theme="true-black"] .fi-modal-window {
        background-color: #050505 !important;
        border-color: rgb(64 64 64 / 1) !important;
    }

    html[data-theme="true-black"] .fi-ta-record {
        background-color: #050505 !important;
        border-color: rgb(64 64 64 / 1) !important;
    }

    html[data-theme="true-black"] .fi-input-wrp {
        background-color: #050505 !important;
        border-color: rgb(82 82 91 / 1) !important;
    }

    html[data-theme="true-black"] .fi-input-wrp:not(.fi-disabled):not(:has(.fi-ac-action:focus)):focus-within {
        border-color: rgb(245 158 11 / 1) !important;
    }

    html[data-theme="true-black"] .fi-input-wrp .fi-input,
    html[data-theme="true-black"] .fi-input-wrp .fi-select-input,
    html[data-theme="true-black"] .fi-input-wrp .fi-textarea {
        background-color: transparent !important;
    }

    html[data-theme="true-black"] .fi-input-wrp .fi-input-wrp-prefix:not(.fi-inline),
    html[data-theme="true-black"] .fi-input-wrp .fi-input-wrp-suffix:not(.fi-inline) {
        border-color: rgb(38 38 38 / 1) !important;
    }

    /*
       GitHub repository button:
       Use a real border for this project-specific button in all themes.
       This avoids Firefox zoom artifacts caused by Filament/Tailwind ring borders.
    */
    .bp-github-link-button {
        border: 2px solid rgb(209 213 219 / 1) !important;
        box-sizing: border-box;
        background-clip: padding-box;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    .bp-github-link-button:hover {
        border-color: rgb(156 163 175 / 1) !important;
    }

    .bp-github-link-button:focus-visible {
        border-color: rgb(245 158 11 / 1) !important;
        outline: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    html.dark .bp-github-link-button,
    html[data-theme="dark"] .bp-github-link-button {
        border-color: rgb(82 82 91 / 1) !important;
    }

    html.dark .bp-github-link-button:hover,
    html[data-theme="dark"] .bp-github-link-button:hover {
        border-color: rgb(113 113 122 / 1) !important;
    }

    html[data-theme="true-black"] .bp-github-link-button {
        border-color: rgb(82 82 91 / 1) !important;
    }

    html[data-theme="true-black"] .bp-github-link-button:hover {
        border-color: rgb(113 113 122 / 1) !important;
    }

    /*
       True Black theme switcher active state.
    */
    html[data-theme="true-black"] .fi-theme-switcher-btn.fi-active {
        background-color: rgb(139 147 255 / 0.18) !important;
        color: rgb(199 210 254 / 1) !important;
    }

    /*
       Logo switching for Filament Admin.
    */
    .fi-logo.logo-dark {
        display: none;
    }

    .fi-logo.logo-light {
        display: inline-block;
    }

    html[data-theme="dark"] .fi-logo.logo-light,
    html[data-theme="true-black"] .fi-logo.logo-light {
        display: none;
    }

    html[data-theme="dark"] .fi-logo.logo-dark,
    html[data-theme="true-black"] .fi-logo.logo-dark {
        display: inline-block;
    }

    @media (prefers-color-scheme: dark) {
        html[data-theme="system"] .fi-logo.logo-light {
            display: none;
        }

        html[data-theme="system"] .fi-logo.logo-dark {
            display: inline-block;
        }
    }
</style>
