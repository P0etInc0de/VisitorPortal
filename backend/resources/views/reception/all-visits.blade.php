<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-av-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Alle Besuche') }}</h1>
            </div>
        </div>
    </x-slot>

    <style>
        .bp-topbar-surface,
        .bp-screen-head.bp-av-screen-head {
            background: hsl(var(--b1)) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            box-shadow: none !important;
            border-bottom: 0 !important;
        }

        .bp-topbar-surface.bp-page-header > .mx-auto {
            padding-top: .42rem !important;
            padding-bottom: .22rem !important;
        }

        .bp-page {
            padding-top: .22rem !important;
            padding-bottom: 1.25rem;
        }

        .bp-screen-title {
            margin: 0;
        }

        .bp-av-page {
            display: grid;
            grid-template-rows: auto auto auto minmax(0, 1fr);
            gap: .45rem;
            align-content: start;
            min-height: calc(100dvh - 7.35rem);
            max-height: calc(100dvh - 7.35rem);
            overflow: visible;
        }

        .bp-av-toolbar {
            display: grid;
            grid-template-columns: minmax(17rem, 1.18fr) auto;
            gap: .65rem;
            align-items: center;
        }

        .bp-av-search-form,
        .bp-av-date-form {
            margin: 0;
        }

        .bp-av-search {
            position: relative;
        }

        .bp-av-search svg {
            position: absolute;
            left: .95rem;
            top: 50%;
            width: 1rem;
            height: 1rem;
            transform: translateY(-50%);
            color: hsl(var(--bc) / .56);
            pointer-events: none;
            z-index: 1;
        }

        .bp-av-search .input {
            width: 100%;
            padding-left: 2.6rem;
            min-height: 2.4rem;
            height: 2.4rem;
            border-radius: 1.15rem;
        }

        .bp-av-date-form {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: nowrap;
            white-space: nowrap;
            min-width: max-content;
        }

        .bp-av-date-form .input {
            width: 7.5rem;
            min-width: 7.5rem;
            max-width: 7.5rem;
            flex: 0 0 7.5rem;
        }

        .bp-av-date-form .input,
        .bp-av-date-form .btn,
        .bp-av-filter-bar .btn {
            min-height: 2.35rem;
            height: 2.35rem;
            padding-top: 0;
            padding-bottom: 0;
            border-radius: 1rem;
        }

        .bp-av-date-form .btn {
            padding-left: .85rem;
            padding-right: .85rem;
        }

        .bp-av-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: -.08rem;
        }

        .bp-av-interaction-hint {
            margin-top: -.16rem;
            padding: 0 .1rem 0;
            font-size: .84rem;
            line-height: 1.25;
            color: hsl(var(--bc) / .62);
        }

        .bp-av-interaction-hint strong {
            font-weight: 600;
            color: hsl(var(--bc) / .74);
        }

        .bp-av-filter-bar .btn {
            padding-left: .9rem;
            padding-right: .9rem;
        }

        .bp-av-table-card {
            min-height: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-self: stretch;
            border: 1px solid hsl(var(--b3));
            background: hsl(var(--b1));
            border-radius: 1.35rem;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .bp-av-table-wrap {
            min-height: 0;
            height: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .bp-av-table {
            min-width: 52rem;
            width: 100%;
            min-height: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .bp-av-table-body {
            min-height: 0;
            flex: 1 1 auto;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .bp-av-table-head,
        .bp-av-visit-row,
        .bp-av-participant-head,
        .bp-av-participant-row {
            display: grid;
            align-items: center;
        }

        .bp-av-table-head,
        .bp-av-visit-row {
            grid-template-columns: 1.85rem minmax(11rem, 1.28fr) minmax(10.4rem, 1.02fr) minmax(6.75rem, .7fr) minmax(5.8rem, .62fr) 3rem minmax(10rem, 1.06fr);
            gap: .45rem;
        }

        .bp-av-table-head {
            position: relative;
            z-index: 1;
            flex: 0 0 auto;
            padding: .44rem .75rem;
            border-bottom: 1px solid hsl(var(--b3));
            background: hsl(var(--b1));
            color: hsl(var(--bc) / .66);
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .bp-av-table-head .bp-av-cell,
        .bp-av-visit-row .bp-av-cell {
            justify-content: flex-start;
        }

        .bp-av-entry {
            border-bottom: 1px solid hsl(var(--b3));
            overflow: hidden;
        }

        .bp-av-entry:last-child {
            border-bottom: 0;
        }

        .bp-av-visit-row {
            padding: .18rem .75rem;
            min-height: 2.35rem;
            background: hsl(var(--b1));
        }

        .bp-av-entry:hover .bp-av-visit-row {
            background: hsl(var(--b2) / .38);
        }

        .bp-av-visit-row {
            cursor: pointer;
        }

        .bp-av-entry.bp-av-entry-expanded .bp-av-visit-row {
            background: hsl(var(--b2) / .3);
        }

        .bp-av-cell {
            min-width: 0;
            display: flex;
            align-items: center;
            overflow: visible;
        }

        .bp-av-cell > * {
            min-width: 0;
        }

        .bp-av-cell-check {
            justify-content: center;
        }

        .bp-av-checkbox {
            width: 1rem;
            height: 1rem;
            border-radius: .35rem;
        }

        .bp-av-title-wrap {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .bp-av-title {
            font-size: .9rem;
            font-weight: 700;
            line-height: 1.18;
            color: hsl(var(--bc));
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bp-av-date-label,
        .bp-av-host,
        .bp-av-count,
        .bp-av-note,
        .bp-av-participant-company,
        .bp-av-participant-status {
            font-size: .82rem;
            line-height: 1.24;
            color: hsl(var(--bc) / .72);
        }

        .bp-av-date-label,
        .bp-av-host,
        .bp-av-note,
        .bp-av-participant-name,
        .bp-av-participant-company,
        .bp-av-participant-status {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bp-av-note {
            display: block;
            max-width: 100%;
        }

        .bp-av-count {
            width: 100%;
            justify-content: center;
            text-align: center;
            font-weight: 600;
            color: hsl(var(--bc) / .82);
        }

        .bp-av-status-text {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
            max-width: 100%;
            font-size: .8rem;
            font-weight: 700;
            line-height: 1.22;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: hsl(var(--bc) / .78);
        }

        .bp-av-entry-details {
            padding: 0 .75rem .55rem 3.05rem;
            background: hsl(var(--b2) / .14);
        }

        .bp-av-participant-table {
            display: grid;
            gap: 0;
            border: 1px solid hsl(var(--bc) / .08);
            border-radius: 1rem;
            background: hsl(var(--b1));
            overflow: hidden;
        }

        .bp-av-participant-head,
        .bp-av-participant-row {
            grid-template-columns: minmax(14rem, 1.55fr) minmax(10rem, 1.1fr) minmax(7rem, .95fr) minmax(5.25rem, .8fr) minmax(9rem, .92fr);
            gap: .55rem;
            padding: .34rem .65rem;
        }

        .bp-av-participant-head {
            background: hsl(var(--b2) / .28);
            border-bottom: 1px solid rgba(15, 23, 42, .10);
            color: hsl(var(--bc) / .74);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .bp-av-participant-row + .bp-av-participant-row {
            border-top: 1px solid hsl(var(--bc) / .06);
        }

        .bp-av-participant-name {
            font-size: .82rem;
            color: hsl(var(--bc));
        }

        .bp-av-action-cell,
        .bp-av-badge-cell {
            width: auto;
        }

        .bp-av-action-cell form,
        .bp-av-badge-cell form {
            width: auto;
            margin: 0;
        }

        .bp-av-badge-cell,
        .bp-av-action-cell {
            justify-content: flex-start;
        }

        .bp-av-row-btn {
            width: auto;
            justify-content: center;
            text-align: center;
            padding-left: .56rem;
            padding-right: .56rem;
        }

        .bp-av-badge-cell form,
        .bp-av-badge-cell .bp-av-row-btn {
            width: auto;
        }

        .bp-av-badge-cell .bp-av-row-btn {
            min-width: 0;
            width: auto;
            padding-left: .34rem;
            padding-right: .34rem;
        }

        .bp-av-action-cell .bp-av-row-btn {
            min-width: var(--bp-av-action-width, 10rem);
            width: var(--bp-av-action-width, 10rem);
            max-width: var(--bp-av-action-width, 10rem);
        }

        .bp-av-empty {
            padding: .7rem .85rem;
            color: hsl(var(--bc) / .7);
            font-size: .88rem;
        }

        @media (max-width: 1400px) {
            .bp-av-toolbar {
                grid-template-columns: minmax(14rem, 1fr) auto;
            }
        }

        @media (max-width: 1100px) {
            .bp-av-page {
                min-height: auto;
                max-height: none;
                overflow: visible;
            }

            .bp-av-toolbar {
                grid-template-columns: 1fr;
            }

            .bp-av-date-form {
                flex-wrap: wrap;
            }

            .bp-av-table-card,
            .bp-av-table-wrap {
                min-height: auto;
                height: auto;
            }
        }
    </style>

    @livewire('reception.all-visits-page')
</x-app-layout>
