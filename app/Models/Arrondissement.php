<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arrondissement extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'slt_arrondissement',
    ];




    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function communes()
    {
        return $this->hasMany(Commune::class);
    }

    public function terrains()
    {
        return $this->hasMany(Terrain::class, 'slt_arrondissement', 'id');
    }
}
