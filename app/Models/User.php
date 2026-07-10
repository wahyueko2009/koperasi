<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'position_id',
        'official_id',
        'name',
        'login',
        'email',
        'password',
        'role',
        'is_active',
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
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'administrator'], true);
    }

    public function isAdministrator(): bool
    {
        return in_array($this->role, ['admin', 'administrator'], true);
    }

    public function isUnitUsaha(): bool
    {
        return $this->role === 'staff';
    }

    public function canAccessUnitUsaha(): bool
    {
        return $this->isAdministrator() || $this->isUnitUsaha();
    }
}
