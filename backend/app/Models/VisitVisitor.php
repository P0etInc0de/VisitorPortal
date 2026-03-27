<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class VisitVisitor extends Pivot
{
    /**
     * The attributes that are mass assignable meaning they can be changed via an array
     *
     * @var list<string>
     */
    protected $fillable = [
        'badge_printed_at',
        'checked_in_at',
        'checked_out_at',
    ];

    public $timestamps = true;
    /**
     * Relationships to table users for users who did check-in/check-out
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_user_id');
    }

}
