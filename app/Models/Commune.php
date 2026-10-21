<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'slt_commune',
    ];

  

    public function arrondissement()
    {
        return $this->belongsTo(Arrondissement::class);
    }

    public function terrains()
    {
        return $this->hasMany(Terrain::class, 'slt_commune', 'id');
    }
}
