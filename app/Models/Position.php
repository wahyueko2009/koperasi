<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    public const OFFICIAL_OPTIONS = [
        ['code' => 'ADMINISTRATOR', 'name' => 'Administrator'],
        ['code' => 'SUPERVISOR', 'name' => 'Supervisor'],
        ['code' => 'ADMIN', 'name' => 'Admin'],
        ['code' => 'FINANCE', 'name' => 'Finance'],
        ['code' => 'UNIT_USAHA', 'name' => 'Unit Usaha'],
        ['code' => 'KETUA_KOPERASI', 'name' => 'Ketua Koperasi'],
        ['code' => 'WAKIL_KETUA', 'name' => 'Wakil Ketua'],
        ['code' => 'BENDAHARA', 'name' => 'Bendahara'],
        ['code' => 'PENASEHAT', 'name' => 'Penasehat'],
        ['code' => 'WAKIL_PENASEHAT', 'name' => 'Wakil Penasehat'],
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'approval_scope',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function officials(): HasMany
    {
        return $this->hasMany(Official::class);
    }
}
