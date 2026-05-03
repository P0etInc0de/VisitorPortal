<style>
    html[data-theme="true-black"] {
        color-scheme: dark;
    }

    /*
       True Black theme for Filament.

       Important:
       Filament renders many visible borders as Tailwind rings using box-shadow
       variables such as --tw-ring-shadow and --tw-ring-color. In Firefox, these
       1px box-shadow/ring based borders can render inconsistently during zoom,
       especially together with border-radius and very dark backgrounds.

       For True Black we therefore use real CSS borders on the affected Filament
       surfaces and controls. This keeps the visible edge stable in Firefox while
       keeping the change limited to the custom true-black theme.
    */
    html[data-theme="true-black"] body,
    html[data-theme="true-black"] .fi-layout,
    html[data-theme="true-black"] .fi-main,
    html[data-theme="true-black"] .fi-sidebar,
    html[data-theme="true-black"] .fi-topbar,
    html[data-theme="true-black"] .fi-header {
        background-color: #000 !important;
    }

    /*
       Contained sections/cards.
       Use real borders as the stable visible edge. Disable the normal ring
       shadow for these surfaces so Firefox does not render broken ring segments
       on top of the real border during zoom.
    */
    html[data-theme="true-black"] .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    html[data-theme="true-black"] .fi-section:not(.fi-section-not-contained).fi-aside > .fi-section-content-ctn,
    html[data-theme="true-black"] .fi-dropdown-panel,
    html[data-theme="true-black"] .fi-modal-window {
        background-color: #050505 !important;
        border: 2px solid rgb(64 64 64 / 1) !important;
        box-sizing: border-box;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    html[data-theme="true-black"] .fi-ta-record {
        background-color: #050505 !important;
        border-color: rgb(64 64 64 / 1) !important;
    }

    /*
       Input/search/select wrappers.
       These are also ring-based in Filament. True Black uses a real 2px border
       instead, so the search field does not lose parts of its border in Firefox
       when zooming.
    */
    html[data-theme="true-black"] .fi-input-wrp {
        background-color: #050505 !important;
        border: 2px solid rgb(82 82 91 / 1) !important;
        box-sizing: border-box;
        overflow: hidden;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    html[data-theme="true-black"] .fi-input-wrp:not(.fi-disabled):not(:has(.fi-ac-action:focus)):focus-within {
        border-color: rgb(245 158 11 / 1) !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    html[data-theme="true-black"] .fi-input-wrp.fi-invalid {
        border-color: rgb(239 68 68 / 1) !important;
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
       Gray outlined buttons.
       The GitHub button uses Filament's outlined gray button variant. Use a real
       border here as well, instead of relying on the ring shadow as the visible
       outline.
    */
    html[data-theme="true-black"] .fi-btn.fi-color-gray.fi-outlined {
        border: 2px solid rgb(82 82 91 / 1) !important;
        box-sizing: border-box;
        background-clip: padding-box;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    html[data-theme="true-black"] .fi-btn.fi-color-gray.fi-outlined:focus-visible {
        border-color: rgb(245 158 11 / 1) !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

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
