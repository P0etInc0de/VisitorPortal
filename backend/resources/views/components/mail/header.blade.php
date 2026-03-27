@props(['url', 'message'])

<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            <img src="{{ $message->embed(public_path('images/logo.png'))  }}"
                 alt="Company Logo"
                 style="height: 120px;
                        max-height: 120px;
                        width: auto;"
            >
        </a>
        <br>
    </td>
</tr>
