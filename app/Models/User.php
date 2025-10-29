<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'matric_no',
        'avatar',
        'status',
        'supervisor_id',
        'department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

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

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the user's 3D avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return $this->avatar;
        }
        
        // Generate 3D avatar using DiceBear API
        $seed = $this->email ?? $this->name ?? 'default';
        return "https://api.dicebear.com/7.x/avataaars-neutral/svg?seed=" . urlencode($seed) . "&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf";
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Get user's primary role name
     */
    public function getPrimaryRole(): string
    {
        return $this->roles()->first()?->name ?? 'student';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Pause the user account
     */
    public function pause(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    /**
     * Activate the user account
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Scope to filter active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter paused users
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Get the department this user belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the supervisor that supervises this student
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the students supervised by this supervisor
     */
    public function students()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Scope to filter supervisors only
     */
    public function scopeSupervisors($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'supervisor');
        });
    }
}
