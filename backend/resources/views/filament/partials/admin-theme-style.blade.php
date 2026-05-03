<style>
    :root {
        --bp-admin-border: rgb(229 231 235 / 1);
        --bp-admin-focus-border: rgb(245 158 11 / 1);
        --bp-admin-panel-bg: rgb(255 255 255 / 1);
        --bp-admin-page-bg: rgb(249 250 251 / 1);
    }

    html.dark,
    html[data-theme="dark"] {
        --bp-admin-border: rgb(63 63 70 / 1);
        --bp-admin-panel-bg: rgb(24 24 27 / 1);
        --bp-admin-page-bg: rgb(9 9 11 / 1);
    }

    html[data-theme="true-black"] {
        color-scheme: dark;
        --bp-admin-border: rgb(38 38 38 / 1);
        --bp-admin-panel-bg: #050505;
        --bp-admin-page-bg: #000;
    }

    /*
       Large surfaces:
       Keep these stable during zoom with a pseudo border.
    */
    .fi-section,
    .fi-dropdown-panel,
    .fi-modal-window,
    .fi-ta-record {
        position: relative;
        background-clip: padding-box !important;
    }

    .fi-section::after,
    .fi-dropdown-panel::after,
    .fi-modal-window::after,
    .fi-ta-record::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        border: 1px solid var(--bp-admin-border);
        border-radius: inherit;
    }

    /*
       Controls:
       Use inset box-shadow instead of a border pseudo element.
       This is more robust for search fields and button-like controls
       that have inner wrappers / prefixes / suffixes.
    */
    .fi-input-wrp,
    .fi-btn,
    .fi-icon-btn,
    .fi-theme-switcher-btn {
        position: relative;
        overflow: hidden !important;
        isolation: isolate;
        background-clip: padding-box !important;
        box-shadow: inset 0 0 0 1px var(--bp-admin-border);
    }

    .fi-input-wrp:focus-within,
    .fi-btn:focus-visible,
    .fi-icon-btn:focus-visible,
    .fi-theme-switcher-btn:focus-visible {
        box-shadow: inset 0 0 0 1px var(--bp-admin-focus-border);
    }

    /*
       Ensure inner children cannot paint square overlays past rounded corners.
    */
    .fi-input-wrp > *,
    .fi-btn > *,
    .fi-icon-btn > *,
    .fi-theme-switcher-btn > * {
        position: relative;
        z-index: 1;
    }

    .fi-input-wrp .fi-input,
    .fi-input-wrp .fi-select-input,
    .fi-input-wrp .fi-textarea {
        background-color: transparent !important;
        background-clip: padding-box !important;
        box-shadow: none !important;
    }

    /*
       True Black surfaces.
    */
    html[data-theme="true-black"] body,
    html[data-theme="true-black"] .fi-layout,
    html[data-theme="true-black"] .fi-main,
    html[data-theme="true-black"] .fi-sidebar,
    html[data-theme="true-black"] .fi-topbar,
    html[data-theme="true-black"] .fi-header {
        background-color: var(--bp-admin-page-bg) !important;
    }

    html[data-theme="true-black"] .fi-section,
    html[data-theme="true-black"] .fi-dropdown-panel,
    html[data-theme="true-black"] .fi-modal-window,
    html[data-theme="true-black"] .fi-ta-record,
    html[data-theme="true-black"] .fi-input-wrp {
        background-color: var(--bp-admin-panel-bg) !important;
        border-color: var(--bp-admin-border) !important;
    }

    html[data-theme="true-black"] .fi-input,
    html[data-theme="true-black"] .fi-select-input,
    html[data-theme="true-black"] .fi-textarea {
        border-color: var(--bp-admin-border) !important;
    }

    html[data-theme="true-black"] .fi-theme-switcher-btn.fi-active {
        background-color: rgb(139 147 255 / 0.18) !important;
        color: rgb(199 210 254 / 1) !important;
    }

    /* Logo switching for Filament Admin */
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
