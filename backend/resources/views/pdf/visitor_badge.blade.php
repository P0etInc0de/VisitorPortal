@php
    $inlineAsset = static function ($path): ?string {
        if (! is_string($path) || $path === '') {
            return null;
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
            font-family: Open Sans, sans-serif;
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
            background: linear-gradient(135deg, #ffffff, #eef2f8);
            @endif
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;

            overflow: hidden;

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .badge-title {
            position: absolute;
            top: 3.4mm;
            right: 4.8mm;

            font-size: 4.7mm;
            font-weight: 600;
            color: #555;
            letter-spacing: 0.2mm;
        }

        .badge-logo {
            position: absolute;
            top: 4.2mm;
            left: 4.8mm;
            height: 7.4mm;
            max-width: 31mm;
            object-fit: contain;
            object-position: left center;
        }

        .label {
            position: absolute;
            color: #da5f00;
            font-weight: bold;
            font-size: 2.9mm;
        }

        .value {
            position: absolute;
            color: #232323;
            font-weight: bold;
            font-size: 3.3mm;
            white-space: nowrap;
            max-width: 65mm;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-name { top: 17.8mm; left: 4.8mm; }
        .value-name { top: 17.8mm; left: 22mm; }

        .label-company { top: 24.6mm; left: 4.8mm; }
        .value-company { top: 24.6mm; left: 22mm; }

        .label-contact { top: 31.4mm; left: 4.8mm; }
        .value-contact { top: 31.4mm; left: 22mm; }

        .label-date { top: 38.2mm; left: 4.8mm; }
        .value-date { top: 38.2mm; left: 22mm; }

        .divider {
            position: absolute;
            left: 4.8mm;
            width: 76mm;
            height: 0.2mm;
            background: #999;
        }

        .divider-1 { top: 22.0mm; }
        .divider-2 { top: 28.8mm; }
        .divider-3 { top: 35.6mm; }
        .divider-4 { top: 42.4mm; }
    </style>
</head>

<body>

<div class="page">
    <div class="badge">

        @if ($logoDataUri)
            <img class="badge-logo" src="{{ $logoDataUri }}" alt="{{ $brandName }}">
        @endif

        <div class="badge-title">
            {{ __('Besucherausweis') }}
        </div>

        <div class="label label-name">{{ __('Name:') }}</div>
        <div class="value value-name">
            {{ trim(implode(' ', array_filter([$visitor->title ?? null, $visitor->first_name ?? null, $visitor->name ?? null]))) }}
        </div>
        <div class="divider divider-1"></div>

        <div class="label label-company">{{ __('Firma:') }}</div>
        <div class="value value-company">
            {{ $visitor->company ?? '-' }}
        </div>
        <div class="divider divider-2"></div>

        <div class="label label-contact">{{ __('Betreuer:') }}</div>
        <div class="value value-contact">
            {{ $visit->responsibleUser?->first_name ?? '-' }} {{ $visit->responsibleUser?->name ?? '' }}
        </div>
        <div class="divider divider-3"></div>

        <div class="label label-date">{{ __('Uhrzeit:') }}</div>
        <div class="value value-date">
            {{ $visit->scheduled_from->format('d.m.Y H:i') . ' - ' . $visit->scheduled_until->format('d.m.Y H:i')}}
        </div>
        <div class="divider divider-4"></div>

    </div>
</div>
</body>
</html>
