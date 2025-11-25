<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WheelSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'type',
        'image',
        'answer',
        'position',
        'is_active',
        'probability',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
        'probability' => 'decimal:2',
    ];
}


