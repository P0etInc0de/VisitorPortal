<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class VisitVisitor extends Pivot
{
    protected $table = 'visit_visitor';

    public $incrementing = false;

    protected $fillable = [
        'visit_id',
        'visitor_id',
        'badge_printed_at',
        'checked_in_at',
        'checked_out_at',
        'checked_in_by_user_id',
        'checked_out_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'badge_printed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function checkedOutByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_user_id');
    }
}
