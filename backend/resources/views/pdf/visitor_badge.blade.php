@php
    $bg = base64_encode(file_get_contents(public_path('images/badge/visitor_badge_background.png')));
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{__('Besucherausweis')}}</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        .page {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .badge-title {
            position: absolute;
            top: 40px;
            right: 56px;

            font-size: 56px;
            font-weight: 600;
            color: #555;

            letter-spacing: 1px;
        }

        .badge {
            width: 1011px;
            height: 636px;
            position: relative;

            background-image: url("data:image/png;base64,{{ $bg }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .label {
            position: absolute;
            color: #ff7a00;
            font-weight: bold;
            font-size: 32px;
        }

        .value {
            position: absolute;
            color: #111;
            font-weight: bold;
            font-size: 38px;
        }

        .label-name { top: 210px; left: 80px; }
        .value-name { top: 205px; left: 370px; }

        .label-company { top: 290px; left: 80px; }
        .value-company { top: 285px; left: 370px; }

        .label-contact { top: 370px; left: 80px; }
        .value-contact { top: 365px; left: 370px; }

        .label-date { top: 450px; left: 80px; }
        .value-date { top: 445px; left: 370px; }

        .divider {
            position: absolute;
            left: 80px;
            width: 850px;
            height: 1px;
            background: #999;
        }

        .divider-1 { top: 260px; }
        .divider-2 { top: 340px; }
        .divider-3 { top: 420px; }
        .divider-4 { top: 500px; }

    </style>

</head>

<body>

<div class="page">
    <div class="badge">
        <div class="badge-title">
            {{__('Besucherausweis')}}
        </div>
        <div class="label label-name">{{__('Name:')}}</div>
        <div class="value value-name">
            {{ $visitor->first_name }} {{ $visitor->last_name }}
        </div>
        <div class="divider divider-1"></div>

        <div class="label label-company">{{__('Firma:')}}</div>
        <div class="value value-company">
            {{ $visitor->company ?? '-' }}
        </div>
        <div class="divider divider-2"></div>

        <div class="label label-contact">{{__('Ansprechpartner:')}}</div>
        <div class="value value-contact">
            {{ $visitor->contact_person ?? '-' }}
        </div>
        <div class="divider divider-3"></div>

        <div class="label label-date">{{__('Besuchszeit:')}}</div>
        <div class="value value-date">
            {{ now()->format('d.m.Y H:i') }}
        </div>
        <div class="divider divider-4"></div>

    </div>
</div>

</body>
</html>
