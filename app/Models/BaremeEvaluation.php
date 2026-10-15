<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaremeEvaluation extends Model
{
    protected $table = 'baremes_evaluation';

    protected $fillable = [
        'groupe',
        'type',
        'code',
        'prix',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'prix' => 'integer',
        'ordre' => 'integer',
        'actif' => 'boolean',
    ];

    public static function pourCalcul()
    {
        return static::query()
            ->where('actif', true)
            ->orderBy('ordre')
            ->orderBy('id')
            ->get(['groupe', 'type', 'code', 'prix', 'ordre', 'actif']);
    }
}
