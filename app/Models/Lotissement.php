<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;

class Lotissement extends Model
{
    //
    use HasFactory; 

    protected $fillable = [ 
        'txt_denomination',
        'txt_nombreParcelle',
        'txt_denomination',
        'slt_departement',
        'slt_arrondissement',
        'slt_commune',
        'txt_surfaceLotissement',
        'txt_documentsAfferants'
    ];

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'slt_departement');
    }

    public function arrondissement()
    {
        return $this->belongsTo(Arrondissement::class, 'slt_arrondissement');
    }

    public function commune()
    {
        return $this->belongsTo(Commune::class, 'slt_commune');
    }

}
