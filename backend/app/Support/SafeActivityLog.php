<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

final class SafeActivityLog
{
    public static function info(string $message, array $context = [], ?string $channel = 'web'): void
    {
        self::write('info', $message, $context, $channel);
    }

    public static function warning(string $message, array $context = [], ?string $channel = 'web'): void
    {
        self::write('warning', $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], ?string $channel = 'web'): void
    {
        self::write('error', $message, $context, $channel);
    }

    private static function write(string $level, string $message, array $context = [], ?string $channel = 'web'): void
    {
        try {
            if ($channel) {
                Log::channel($channel)->{$level}($message, $context);

                return;
            }

            Log::{$level}($message, $context);
        } catch (Throwable) {
            // Logging must never break the user flow during development.
        }
    }
}
