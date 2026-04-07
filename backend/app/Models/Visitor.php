<?php

namespace App\Models;

use App\Enums\VisitorStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Visitor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'name',
        'title',
        'gender',
        'email',
        'phone',
        'company',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => VisitorStatusEnum::class,
    ];

    /**
     * Relationship to table visits via pivot table
     * EXAMPLE HOW TO ACCESS: name of user who checked in first visitor
     *                          --> $visit->visitors->first()->pivot->checkedInBy->name
     * INFO: ALL IMPORTANT INFO LIKE CHECKED_IN_AT LIES IN PIVOT OBJECT CALLED PIVOT!!!
     */
    public function visits(): BelongsToMany
    {
        return $this->belongsToMany(Visit::class, 'visit_visitor')
            ->using(VisitVisitor::class)
            ->withPivot([ // load extra stuff into object called pivot
                'badge_printed_at',
                'checked_in_at',
                'checked_out_at',
                'checked_in_by_user_id',
                'checked_out_by_user_id',
            ]);
    }

    /**
     * Get next scheduled visit
     */
    public function nextVisit()
    {
        return $this->visits()
            ->where('scheduled_from', '>', Carbon::now())
            ->orderBy('scheduled_from', 'asc')
            ->first();
    }
}
