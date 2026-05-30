<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitUsahaService extends Model
{
    protected $table = 'unit_usaha_services';

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
