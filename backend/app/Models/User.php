<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\GenderEnum;
use App\Support\AppMfaPolicy;
use App\Support\RecoveryCodeManager;
use App\Support\SafeLogContext;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'name',
        'first_name',
        'title',
        'email',
        'locale',
        'theme_preference',
        'password',
        'department_id',
        'gender',
        'is_active',
        'local_login_allowed',
        'deactivated_at',
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
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Relationship of  tables
     * EXAMPLE HOW TO ACCESS: Get department that user belogs to via foreign key
     *                          --> $department = $user->department->name/location/...
     * EXAMPLE HOW TO ACCESS: Get visits hosted by a user
     *                           --> visits = $user->hostedVisits
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id'); // Relationship to departments table id
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function hostedVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'host_user_id');
    }

    public function substitutedVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'substitute_user_id');
    }

    public function canceledVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'canceled_by_user_id');
    }

    public function identities(): HasMany
    {
        return $this->hasMany(UserIdentity::class);
    }

    public function performedCheckIns(): HasMany
    {
        return $this->hasMany(VisitVisitor::class, 'checked_in_by_user_id');
    }

    public function performedCheckOuts(): HasMany
    {
        return $this->hasMany(VisitVisitor::class, 'checked_out_by_user_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if (blank($user->site_id)) {
                $user->site_id = $user->department_id
                    ? Department::query()->whereKey($user->department_id)->value('site_id')
                    : Site::default()->id;
            }

            if (filled($user->department_id)) {
                $departmentSiteId = Department::query()->whereKey($user->department_id)->value('site_id');

                if ($departmentSiteId !== null && (int) $departmentSiteId !== (int) $user->site_id) {
                    throw ValidationException::withMessages([
                        'department_id' => __('Die Abteilung muss zum ausgewählten Standort gehören.'),
                    ]);
                }
            }

            if (! $user->isDirty('is_active')) {
                return;
            }

            $user->deactivated_at = $user->is_active ? null : ($user->deactivated_at ?? now());
        });

        static::saved(function (self $user): void {
            $user->syncPrimarySiteAssignment();
        });
    }

    public function syncPrimarySiteAssignment(): void
    {
        if (filled($this->site_id)) {
            $this->sites()->syncWithoutDetaching([(int) $this->site_id]);
        }
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)->withTimestamps();
    }

    /**
     * @return Collection<int, int>
     */
    public function assignedSiteIds(): Collection
    {
        $siteIds = $this->relationLoaded('sites')
            ? $this->sites->pluck('id')
            : $this->sites()->pluck('sites.id');

        if (filled($this->site_id)) {
            $siteIds->push((int) $this->site_id);
        }

        return $siteIds
            ->map(fn ($siteId): int => (int) $siteId)
            ->unique()
            ->values();
    }

    public function canAccessSite(null|int|string $siteId): bool
    {
        if (blank($siteId)) {
            return false;
        }

        return $this->assignedSiteIds()->contains((int) $siteId);
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
            'is_active' => 'boolean',
            'local_login_allowed' => 'boolean',
            'deactivated_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function canLoginLocally(): bool
    {
        if ($this->local_login_allowed === false) {
            return false;
        }

        return config('sso.auth_mode') !== 'sso_only'
            || $this->can('LoginLocallyInSsoOnlyMode');
    }

    public function hasConfirmedTwoFactorAuthentication(): bool
    {
        return filled($this->two_factor_secret)
            && filled($this->two_factor_confirmed_at);
    }

    public function requiresTwoFactorAuthentication(?string $authMethod = null): bool
    {
        return app(AppMfaPolicy::class)->isRequiredForAuthMethod($this, $authMethod ?: 'local');
    }

    public function replaceRecoveryCode($code): void
    {
        if (! app(RecoveryCodeManager::class)->consume($this, (string) $code)) {
            throw ValidationException::withMessages([
                'recovery_code' => [__('The provided two factor recovery code was invalid.')],
            ]);
        }

        if (app()->bound('request') && request()->hasSession()) {
            request()->session()->put('auth.two_factor_login_method', 'recovery_code');
        }
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check if the user has the Superuser or admin role (Shield's default is 'super_admin')
        if ($this->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        Log::channel('web')->info('Authorization failed.', [
            ...SafeLogContext::authorization($this, 'accessPanel'),
            'resource_type' => 'FilamentPanel',
            'resource_id' => $panel->getId(),
        ]);

        return false;
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['first_name'].' '.$attributes['name'], );
    }
}
