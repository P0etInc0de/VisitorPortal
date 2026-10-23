<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Kiosk extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'walk_in_host_user_id',
        'created_by_user_id',
        'name',
        'token',
        'is_active',
        'allow_walk_in',
        'allow_check_out',
        'require_signature',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_walk_in' => 'boolean',
        'allow_check_out' => 'boolean',
        'require_signature' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function walkInHost(): BelongsTo
    {
        return $this->belongsTo(User::class, 'walk_in_host_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function url(): string
    {
        return route('kiosk.show', ['token' => $this->token]);
    }

    public function getPublicUrlAttribute(): string
    {
        return $this->url();
    }

    public function regenerateToken(): void
    {
        $this->forceFill(['token' => self::generateToken()])->save();
    }

    public static function generateToken(): string
    {
        return Str::lower(Str::random(48));
    }

    public static function findByToken(string $token): ?self
    {
        return self::query()->where('token', $token)->first();
    }

    protected static function booted(): void
    {
        static::creating(function (self $kiosk): void {
            if (blank($kiosk->token)) {
                $kiosk->token = self::generateToken();
            }
        });
    }
}
