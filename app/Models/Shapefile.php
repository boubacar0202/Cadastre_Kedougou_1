<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shapefile extends Model
{
    protected $fillable = [
        'nom',
        'nom_table',
        'srid',
        'nb_entites',
        'bbox',
        'couleur',
        'visible',
        'user_id',
    ];

    protected $casts = [
        'bbox'       => 'array',
        'visible'    => 'boolean',
        'nb_entites' => 'integer',
        // ⚠️ 'srid' volontairement PAS casté en integer
        //    (reste une string, ex: "32628", "4326")
    ];

    protected $attributes = [
        'couleur'    => '#3388ff',
        'srid'       => '4326',
        'visible'    => true,
        'nb_entites' => 0,
    ];
}