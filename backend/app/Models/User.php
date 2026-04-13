<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\GenderEnum;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'title',
        'email',
        'password',
        'department_id',
        'gender',
    ];

    protected $casts = [
        'gender' => GenderEnum::class,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Relationship of  tables
     * EXAMPLE HOW TO ACCESS: Get department that user belogs to via foreign key
     *                          --> $department = $user->department->name/location/...
     * EXAMPLE HOW TO ACCESS: Get vists that user is responsible for
     *                           --> visits = $user->responsibleVisits
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id'); // Relationship to departments table id
    }

    public function visit(): HasMany
    {
        return $this->hasMany(Visit::class, 'user_id'); // Relationship to visits table id
    }

    public function responsibleVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'responsible_user_id'); // Relationship to visits table responsible_user_id
    }

    public function canceledVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'canceled_user_id'); // Relationship to visits table canceled_user_id
    }

    public function performedCheckIns(): HasMany
    {
        return $this->hasMany(VisitVisitor::class, 'checked_in_by_user_id');
    }

    public function performedCheckOuts(): HasMany
    {
        return $this->hasMany(VisitVisitor::class, 'checked_out_by_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Check if the user has the Superuser role (Shield's default is 'super_admin')
        return $this->hasRole('super_admin');
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['first_name'].' '.$attributes['name'], );
    }
}
