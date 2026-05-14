@php
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

    if (! in_array($badgeDesign, ['standard', 'photo_qr'], true)) {
        $badgeDesign = 'standard';
    }

    if (! preg_match('/^#[0-9a-fA-F]{6}$/', $badgeAccentColor)) {
        $badgeAccentColor = '#ff8a00';
    }

    $visitorFullName = trim(implode(' ', array_filter([$visitor->title ?? null, $visitor->first_name ?? null, $visitor->name ?? null]))) ?: '-';
    $responsibleFullName = trim(($visit->responsibleUser?->first_name ?? '').' '.($visit->responsibleUser?->name ?? ''));
    $scheduledRange = $visit->scheduled_from->isSameDay($visit->scheduled_until)
        ? $visit->scheduled_from->format('d.m.Y').' | '.$visit->scheduled_from->format('H:i').' - '.$visit->scheduled_until->format('H:i').' Uhr'
        : $visit->scheduled_from->format('d.m.Y H:i').' - '.$visit->scheduled_until->format('d.m.Y H:i').' Uhr';
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
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Besucherausweis') }}</title>

    <style>
        @page {
            size: 85.6mm 53.98mm;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 85.6mm;
            height: 53.98mm;
            background: #b8bec8;
            font-family: "Open Sans", Arial, sans-serif;
            overflow: hidden;
        }

        .badge {
            position: relative;
            width: 85.6mm;
            height: 53.98mm;
            background: #b8bec8;
            overflow: hidden;
            --badge-accent: {{ $badgeAccentColor }};

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .surface-base {
            position: absolute;
            inset: -3mm;
            z-index: 0;
            background: linear-gradient(112deg, #ffffff 0%, #f8fafc 37%, #eef2f7 70%, #dfe5ed 100%);
        }

        .surface-accent {
            position: absolute;
            top: -2mm;
            right: -2.5mm;
            z-index: 1;
            width: 63mm;
            height: 14.6mm;
            overflow: visible;
            filter: drop-shadow(0 1.2mm 1.5mm rgba(17, 24, 39, 0.22));
        }

        .surface-footer {
            position: absolute;
            left: -3mm;
            right: -3mm;
            bottom: -3mm;
            z-index: 1;
            width: 91.6mm;
            height: 17mm;
            overflow: visible;
        }

        .badge-logo {
            position: absolute;
            top: 3.1mm;
            left: 5.9mm;
            z-index: 2;
            height: 6.6mm;
            max-width: 29mm;
            object-fit: contain;
            object-position: left center;
        }

        .badge-title {
            position: absolute;
            top: 2.6mm;
            right: 6.8mm;
            z-index: 2;
            color: #000000;
            font-size: 5.7mm;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.1mm;
            text-shadow: 0 0.45mm 0.7mm rgba(17, 24, 39, 0.28);
        }

        .content {
            position: absolute;
            top: 17.1mm;
            left: 5.4mm;
            right: 5.4mm;
            bottom: 4.8mm;
            z-index: 2;
        }

        .badge--photo_qr .content {
            right: 33mm;
        }

        .visitor-name {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            color: #000000;
            font-size: 6.5mm;
            font-weight: 800;
            line-height: 1;
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
            font-size: 3.6mm;
            font-weight: 800;
            line-height: 5mm;
            white-space: nowrap;
        }

        .detail-value {
            position: absolute;
            left: 28.5mm;
            right: 0;
            top: 0;
            color: #000000;
            font-size: 3.6mm;
            font-weight: 800;
            line-height: 5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .row-company { top: 9.5mm; }
        .row-host { top: 15.95mm; }
        .row-time { top: 22.4mm; }

        .row-time .detail-value {
            font-size: 3.35mm;
        }

        .separator {
            position: absolute;
            left: -0.7mm;
            width: 75.8mm;
            height: 0.22mm;
            background: rgba(95, 101, 110, 0.24);
        }

        .separator-company { top: 14.25mm; }
        .separator-host { top: 20.72mm; }
        .separator-time { top: 27.15mm; }

        .badge--photo_qr .visitor-name { font-size: 4.85mm; }
        .badge--photo_qr .detail-label,
        .badge--photo_qr .detail-value { font-size: 2.35mm; }
        .badge--photo_qr .detail-value { left: 20mm; }
        .badge--photo_qr .separator { width: 44mm; }
        .badge--photo_qr .row-time .detail-value { font-size: 2.35mm; }

        .media-column {
            position: absolute;
            top: 12.2mm;
            right: 5.2mm;
            z-index: 2;
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
    <div class="surface-base"></div>

    <svg class="surface-accent" viewBox="0 0 660 150" preserveAspectRatio="none" aria-hidden="true">
        <path d="M118 -30 H700 V126 H196 C150 126 132 102 116 58 L82 -30 Z" fill="{{ $badgeAccentColor }}"/>
    </svg>

    <svg class="surface-footer" viewBox="0 0 1080 210" preserveAspectRatio="none" aria-hidden="true">
        <path d="M-40 92 C228 160 698 132 1120 22 V240 H-40 Z" fill="#b8bec8"/>
    </svg>

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
            <div class="detail-value">{{ $responsibleFullName !== '' ? $responsibleFullName : '-' }}</div>
        </div>
        <div class="separator separator-host"></div>

        <div class="detail-row row-time">
            <div class="detail-label">{{ __('Uhrzeit:') }}</div>
            <div class="detail-value">{{ $scheduledRange }}</div>
        </div>
        <div class="separator separator-time"></div>
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
