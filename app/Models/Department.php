<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get all users (students and supervisors) in this department
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get only students in this department
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        });
    }

    /**
     * Get only supervisors in this department
     */
    public function supervisors(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function ($query) {
            $query->where('name', 'supervisor');
        });
    }

    /**
     * Scope to get only active departments
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
