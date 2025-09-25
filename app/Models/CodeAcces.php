<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeAcces extends Model
{
    // 
    protected $table = 'code_acces';

    // Colonnes que l'on peut remplir via mass-assignment
    protected $fillable = [
        'code',
        'utilise',
    ];

    // Si tu veux que 'utilise' soit un boolean automatiquement
    protected $casts = [
        'utilise' => 'boolean',
    ];

    // Si tu n’utilises pas les timestamps
    // public $timestamps = false;
}
