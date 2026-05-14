@php
    $brandName = config('branding.name', 'VisitorPortal');
    $mailLogoPath = config('branding.mail_logo');
    $hasMailLogo = is_string($mailLogoPath) && $mailLogoPath !== '' && file_exists(public_path($mailLogoPath));
@endphp

<div>
    <tr>
        <td class="header">
            <a href="{{ $url }}" style="display: inline-block;">
                @if ($hasMailLogo)
                    <img src="{{ $message->embed(public_path($mailLogoPath)) }}"
                         alt="{{ $brandName }}"
                         style="height: 120px; max-height: 120px; width: auto;"
                    >
                @else
                    {{ $brandName }}
                @endif
            </a>
            <br>
        </td>
    </tr>

</div>
