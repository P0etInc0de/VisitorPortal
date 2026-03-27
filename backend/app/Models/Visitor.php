<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     * Relationship to table users
 *
* public function users(): HasMany
    * {
        * return $this->hasMany(User::class);
    * }*/
}
