<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'slt_departement',
        'region_id',
    ];



    public function regions()
    {
        return $this->belongsTo(Region::class);
    }

    public function arrondissements()
    {
        return $this->hasMany(Arrondissement::class);
    }

    public function terrains()
    {
        return $this->hasMany(Terrain::class, 'slt_departement', 'id');
    }
}
