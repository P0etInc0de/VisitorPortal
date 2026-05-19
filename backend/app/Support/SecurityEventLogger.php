<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class SecurityEventLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(Request $request, string $event, array $context = []): void
    {
        Log::channel('web')->info($event, [
            'user_id' => $request->user()?->getAuthIdentifier(),
            'auth_method' => (string) $request->session()->get('auth.method', 'local'),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            ...$context,
        ]);
    }
}
