<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Visit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'scheduled_from',
        'scheduled_until',
        'status',
        'notes',
    ];

    /**
     * Relationships to table users for all foreign keys
     * EXAMPLE HOW TO ACCESS: Access user who ist responsible for a visit
     *                         --> $user = $visit->responsibleUser()
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by_user_id');
    }

    /**
     * Relationship to table visits via pivot table
     * INFO: ALL IMPORTANT INFO LIKE CHECKED_IN_AT LIES IN PIVOT OBJECT CALLED PIVOT!!!
     */
    public function visitors(): BelongsToMany
    {
        return $this->belongsToMany(Visitor::class, 'visit_visitor')
            ->using(VisitVisitor::class)
            ->withPivot([ // load extra stuff into object called pivot
                'badge_printed_at',
                'checked_in_at',
                'checked_out_at',
                'checked_in_by_user_id',
                'checked_out_by_user_id',
            ]);
    }
}
