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

    $backgroundDataUri = $inlineAsset(config('branding.badge_background'));
    $logoDataUri = $inlineAsset(config('branding.badge_logo', config('branding.logo_light')));
    $brandName = config('branding.name', 'VisitorPortal');
    $badgeDesign = (string) config('branding.badge_design', 'standard');

    if (! in_array($badgeDesign, ['standard', 'photo_qr'], true)) {
        $badgeDesign = 'standard';
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
        html, body {
            margin: 0;
            padding: 0;
            width: 85.6mm;
            height: 53.98mm;
            font-family: "Open Sans", Arial, sans-serif;
            overflow: hidden;
        }

        @page {
            size: 85.6mm 53.98mm; /* Credit Card Size */
            margin: 0;
        }

        .page {
            width: 85.6mm;
            height: 53.98mm;
        }

        .badge {
            position: relative;
            width: 85.6mm;
            height: 53.98mm;

            @if ($backgroundDataUri)
            background-image: url("{{ $backgroundDataUri }}");
            @else
            background: linear-gradient(135deg, #ffffff 0%, #f6f8fb 48%, #eef2f8 100%);
            @endif
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;

            overflow: hidden;

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .badge-logo {
            position: absolute;
            top: 2.2mm;
            left: 5.1mm;
            height: 7.7mm;
            max-width: 35mm;
            object-fit: contain;
            object-position: left center;
        }

        .badge-title {
            position: absolute;
            top: 2.35mm;
            right: 5mm;
            color: #4b5563;
            font-size: 5mm;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 0.04mm;
            text-shadow: 0 0.25mm 0.55mm rgba(17, 24, 39, 0.28);
        }

        .content {
            position: absolute;
            top: 17.2mm;
            left: 5.55mm;
            right: 5.55mm;
            bottom: 4.8mm;
        }

        .badge--photo_qr .content {
            right: 33mm;
        }

        .visitor-name {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            color: #050505;
            font-size: 6.35mm;
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
            width: 16.7mm;
            color: #5e5e5e;
            font-size: 3.55mm;
            font-weight: 800;
            line-height: 5mm;
        }

        .detail-value {
            position: absolute;
            left: 20.4mm;
            right: 0;
            top: 0;
            color: #050505;
            font-size: 3.55mm;
            font-weight: 800;
            line-height: 5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .row-company { top: 9.25mm; }
        .row-host { top: 15.8mm; }
        .row-time { top: 22.2mm; }

        .row-time .detail-value {
            font-size: 3.4mm;
        }

        .separator {
            position: absolute;
            left: -0.6mm;
            width: 66.2mm;
            height: 0.3mm;
            background: rgba(80, 86, 94, 0.2);
        }

        .separator-company { top: 14.05mm; }
        .separator-host { top: 20.55mm; }
        .separator-time { top: 27.1mm; }

        .badge--photo_qr .visitor-name { font-size: 4.85mm; }
        .badge--photo_qr .detail-label,
        .badge--photo_qr .detail-value { font-size: 2.75mm; }
        .badge--photo_qr .detail-value { left: 15.8mm; }
        .badge--photo_qr .separator { width: 44mm; }
        .badge--photo_qr .row-time .detail-value { font-size: 2.55mm; }

        .media-column {
            position: absolute;
            top: 12.2mm;
            right: 5.2mm;
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
            color: #7a8797;
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
            color: #6b7280;
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
            color: #2f3641;
            font-size: 4.2mm;
            font-weight: 800;
            letter-spacing: 0.3mm;
        }
    </style>
</head>

<body>
<div class="page">
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
                <div class="detail-label">{{ __('Firma:') }}</div>
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
</div>
</body>
</html>
