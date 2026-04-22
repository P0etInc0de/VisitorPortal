<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\VisitorStatusEnum;
use Database\Factories\VisitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class Visitor extends Model
{
    /** @use HasFactory<VisitorFactory> */
    use HasFactory, Notifiable;

    /**
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
        'gender' => GenderEnum::class,
    ];

    public function visits(): BelongsToMany
    {
        return $this->belongsToMany(Visit::class, 'visit_visitor')
            ->using(VisitVisitor::class)
            ->withPivot([
                'badge_printed_at',
                'checked_in_at',
                'checked_out_at',
                'checked_in_by_user_id',
                'checked_out_by_user_id',
                'notes',
            ])
            ->withTimestamps();
    }
}
