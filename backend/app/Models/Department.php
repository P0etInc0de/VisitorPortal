<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'location',
        'is_active',
    ];

    /**
     * Relationship to table users
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
