@php
    use App\Support\BadgePdfDimensions;

    $inlineAsset = static function ($path): ?string {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        $fullPath = public_path($path);

        if (! file_exists($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($fullPath));
    };

    $brandName = config('branding.name', 'VisitorPortal');
    $logoDataUri = $inlineAsset(config('branding.badge_logo', config('branding.logo_light')));
    $badgeDesign = (string) config('branding.badge_design', 'standard');
    $badgeAccentColor = (string) config('branding.badge_accent_color', '#ff8a00');
    $badgePdfWidth = BadgePdfDimensions::cssMediaWidth();
    $badgePdfHeight = BadgePdfDimensions::cssMediaHeight();

    if (! in_array($badgeDesign, ['standard', 'photo_qr'], true)) {
        $badgeDesign = 'standard';
    }

    if (! preg_match('/^#[0-9a-fA-F]{6}$/', $badgeAccentColor)) {
        $badgeAccentColor = '#ff8a00';
    }

    $mixHexColor = static function (string $hexColor, string $targetHexColor, float $amount): string {
        $hexColor = ltrim($hexColor, '#');
        $targetHexColor = ltrim($targetHexColor, '#');

        $channels = array_map(
            static fn (int $offset): int => hexdec(substr($hexColor, $offset, 2)),
            [0, 2, 4]
        );
        $targetChannels = array_map(
            static fn (int $offset): int => hexdec(substr($targetHexColor, $offset, 2)),
            [0, 2, 4]
        );

        $mixedChannels = array_map(
            static fn (int $channel, int $targetChannel): int => (int) round($channel + (($targetChannel - $channel) * $amount)),
            $channels,
            $targetChannels
        );

        return '#'.implode('', array_map(static fn (int $channel): string => str_pad(dechex($channel), 2, '0', STR_PAD_LEFT), $mixedChannels));
    };

    $badgeAccentStartColor = $mixHexColor($badgeAccentColor, '#ffffff', 0.24);
    $badgeBackgroundSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="21.993 21.878 856.014 540.244" preserveAspectRatio="none">
    <defs>
        <linearGradient id="badgeBase" x1="0" x2="1" y1="0" y2="1">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="0.44" stop-color="#f8fafc"/>
            <stop offset="0.76" stop-color="#e7edf4"/>
            <stop offset="1" stop-color="#dce3ec"/>
        </linearGradient>
        <linearGradient id="badgeAccent" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="{$badgeAccentStartColor}"/>
            <stop offset="0.72" stop-color="{$badgeAccentColor}"/>
            <stop offset="1" stop-color="{$badgeAccentColor}"/>
        </linearGradient>
    </defs>

    <rect x="-40" y="-40" width="980" height="664" fill="url(#badgeBase)"/>
    <path d="M305 -40 H980 V108 H438 C407 108 397 101 387 84 L332 -40 Z" fill="url(#badgeAccent)"/>
    <path d="M-50 468 C170 538 560 522 950 398 V630 H-50 Z" fill="#b8bec8"/>
</svg>
SVG;
    $badgeBackgroundDataUri = 'data:image/svg+xml,'.rawurlencode($badgeBackgroundSvg);

    $visitorFullName = trim(implode(' ', array_filter([$visitor->title ?? null, $visitor->first_name ?? null, $visitor->name ?? null]))) ?: '-';
    $hostFullName = trim(($visit->host?->first_name ?? '').' '.($visit->host?->name ?? ''));
    $visitDate = $visit->scheduled_from->format('d.m.Y');
    $visitTimeSeparator = __('bis');
    $visitTimeSuffix = __('Uhr');
    $visitTimeRange = $visit->scheduled_from->isSameDay($visit->scheduled_until)
        ? $visit->scheduled_from->format('H:i').' '.$visitTimeSeparator.' '.$visit->scheduled_until->format('H:i').' '.$visitTimeSuffix
        : $visit->scheduled_from->format('d.m.Y H:i').' '.$visitTimeSeparator.' '.$visit->scheduled_until->format('d.m.Y H:i').' '.$visitTimeSuffix;
    $photoDataUri = $inlineAsset(
        data_get($visitor, 'badge_photo_path')
            ?: data_get($visitor, 'photo_path')
            ?: data_get($visitor, 'avatar_path')
    );
    $qrCodeDataUri = data_get($visitor, 'badge_qr_code_data_uri')
        ?: data_get($visit, 'badge_qr_code_data_uri')
        ?: data_get($visitor, 'badge_qr_code_path')
        ?: data_get($visit, 'badge_qr_code_path');
    $qrCodeDataUri = $inlineAsset($qrCodeDataUri);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Besucherausweis') }}</title>

    <style>
        @page {
            size: {{ $badgePdfWidth }} {{ $badgePdfHeight }};
            margin: 0;
        }

        html {
            position: fixed;
            inset: 0;
            margin: 0;
            padding: 0;
            width: {{ $badgePdfWidth }};
            height: {{ $badgePdfHeight }};
            background: url("{{ $badgeBackgroundDataUri }}") 0 0 / 100% 100% no-repeat;
            font-family: "Open Sans", Arial, sans-serif;
            overflow: hidden;

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            width: {{ $badgePdfWidth }};
            height: {{ $badgePdfHeight }};
            background: transparent;
            font-family: "Open Sans", Arial, sans-serif;
            overflow: hidden;
        }

        .badge {
            position: fixed;
            inset: 0;
            width: {{ $badgePdfWidth }};
            height: {{ $badgePdfHeight }};
            overflow: hidden;
            background: transparent;

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /*
         * PDF rendering note: Gotenberg/Chromium can rasterize CSS/SVG filters,
         * drop-shadow, text-shadow and some SVG effects during PDF export. That can
         * create pixelated edges, blur and anti-aliased page-edge stripes. Keep the
         * badge artwork in one root page background without filters or shadows.
         * Chromium/Gotenberg also quantizes requested paper sizes. Do not hardcode
         * badge width/height in mm here. The HTML size must exactly match the
         * measured PDF MediaBox from App\Support\BadgePdfDimensions, otherwise
         * unpainted page area appears as white strips on the PDF edges.
         * Embedded SVG surfaces were removed because Chromium clipped their viewport
         * a fraction inside the MediaBox and exposed a white right-edge strip. Keep
         * the artwork out of child surface nodes, because Chromium also clips those
         * child paint groups before the MediaBox.
         */
        .badge-logo {
            position: absolute;
            top: 0.35mm;
            left: 5.45mm;
            z-index: 1;
            height: 9.8mm;
            max-width: 38mm;
            object-fit: contain;
            object-position: left center;
        }

        .badge-title {
            position: absolute;
            top: 1.85mm;
            left: 37.5mm;
            right: 3.2mm;
            z-index: 1;
            color: #000000;
            font-size: 5.05mm;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.12mm;
            text-align: center;
        }

        .content {
            position: absolute;
            top: 14.05mm;
            left: 5.2mm;
            right: 5.2mm;
            bottom: 3.8mm;
            z-index: 1;
        }

        .badge--photo_qr .content {
            right: 33mm;
        }

        .visitor-name {
            position: absolute;
            top: -0.45mm;
            left: 0;
            right: 0;
            color: #000000;
            font-size: 6.55mm;
            font-weight: 800;
            line-height: 1.16;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .detail-row {
            position: absolute;
            left: 0;
            right: 0;
            height: 5mm;
        }

        .detail-label {
            position: absolute;
            left: 0;
            top: 0;
            width: 24mm;
            color: #5f5f5f;
            font-size: 3.55mm;
            font-weight: 800;
            line-height: 5mm;
            white-space: nowrap;
        }

        .detail-value {
            position: absolute;
            left: 28.6mm;
            right: 0;
            top: 0;
            color: #000000;
            font-size: 3.55mm;
            font-weight: 800;
            line-height: 5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .row-company { top: 9.15mm; }
        .row-host { top: 15.45mm; }

        .separator {
            position: absolute;
            left: -0.5mm;
            width: 76mm;
            height: 0.22mm;
            background: rgba(95, 101, 110, 0.24);
        }

        .separator-company { top: 13.95mm; }
        .separator-host { top: 20.25mm; }

        .visit-date {
            position: absolute;
            left: -0.6mm;
            top: 22mm;
            color: #a7a7a7;
            font-size: 5.25mm;
            font-weight: 800;
            line-height: 7.2mm;
            white-space: nowrap;
        }

        .visit-time {
            position: absolute;
            left: 29mm;
            top: 22.95mm;
            right: 0;
            color: #a7a7a7;
            font-size: 2.95mm;
            font-weight: 500;
            line-height: 5.4mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge--photo_qr .visitor-name { font-size: 4.85mm; }
        .badge--photo_qr .detail-label,
        .badge--photo_qr .detail-value { font-size: 2.35mm; }
        .badge--photo_qr .detail-value { left: 20mm; }
        .badge--photo_qr .separator { width: 44mm; }
        .badge--photo_qr .visit-date { font-size: 3.3mm; }
        .badge--photo_qr .visit-time { left: 16mm; font-size: 2.1mm; }

        .media-column {
            position: absolute;
            top: 12.2mm;
            right: 5.2mm;
            z-index: 1;
            width: 23.5mm;
            bottom: 4.7mm;
        }

        .badge-photo {
            width: 23.5mm;
            height: 25.5mm;
            border: 0.35mm solid rgba(47, 54, 65, 0.26);
            border-radius: 2mm;
            object-fit: cover;
            background: #f8fafc;
        }

        .badge-photo--placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000000;
            font-size: 3.2mm;
            font-weight: 700;
            letter-spacing: 0.2mm;
            text-transform: uppercase;
        }

        .qr-row {
            position: absolute;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: flex-end;
            gap: 2mm;
        }

        .qr-label {
            color: #000000;
            font-size: 2.1mm;
            font-weight: 700;
            line-height: 1.1;
            text-align: right;
            text-transform: uppercase;
        }

        .qr-code {
            width: 14.4mm;
            height: 14.4mm;
            border: 0.3mm solid rgba(47, 54, 65, 0.3);
            border-radius: 1.2mm;
            object-fit: contain;
            background: #ffffff;
        }

        .qr-code--placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000000;
            font-size: 4.2mm;
            font-weight: 800;
            letter-spacing: 0.3mm;
        }
    </style>
</head>

<body>
<div class="badge badge--{{ $badgeDesign }}">
    @if ($logoDataUri)
        <img class="badge-logo" src="{{ $logoDataUri }}" alt="{{ $brandName }}">
    @endif

    <div class="badge-title">
        {{ __('Besucherausweis') }}
    </div>

    <div class="content">
        <div class="visitor-name">{{ $visitorFullName }}</div>

        <div class="detail-row row-company">
            <div class="detail-label">{{ __('Unternehmen:') }}</div>
            <div class="detail-value">{{ $visitor->company ?? '-' }}</div>
        </div>
        <div class="separator separator-company"></div>

        <div class="detail-row row-host">
            <div class="detail-label">{{ __('Betreuer:') }}</div>
            <div class="detail-value">{{ $hostFullName !== '' ? $hostFullName : '-' }}</div>
        </div>
        <div class="separator separator-host"></div>

        <div class="visit-date">{{ $visitDate }}</div>
        <div class="visit-time">{{ $visitTimeRange }}</div>
    </div>

    @if ($badgeDesign === 'photo_qr')
        <div class="media-column">
            @if ($photoDataUri)
                <img class="badge-photo" src="{{ $photoDataUri }}" alt="{{ $visitorFullName }}">
            @else
                <div class="badge-photo badge-photo--placeholder">{{ __('Foto') }}</div>
            @endif

            <div class="qr-row">
                <div class="qr-label">{{ __('Scan') }}</div>

                @if ($qrCodeDataUri)
                    <img class="qr-code" src="{{ $qrCodeDataUri }}" alt="{{ __('QR-Code') }}">
                @else
                    <div class="qr-code qr-code--placeholder">QR</div>
                @endif
            </div>
        </div>
    @endif
</div>
</body>
</html>
