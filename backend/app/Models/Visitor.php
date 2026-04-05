<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Visitor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'status',
        'notes',
    ];

    /**
     * Relationship to table visits via pivot table
     * EXAMPLE HOW TO ACCESS: name of user who checked in first visitor
     *                          --> $visit->visitors->first()->pivot->checkedInBy->name
     * INFO: ALL IMPORTANT INFO LIKE CHECKED_IN_AT LIES IN PIVOT OBJECT CALLED PIVOT!!!
     */
    public function visits(): belongsToMany
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
}
