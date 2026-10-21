<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'slt_region',
    ];




    public function departements()
    {
        return $this->hasMany(Departement::class);
    }

    public function terrains()
    {
        return $this->hasMany(Terrain::class, 'slt_region', 'id');
    }
}
