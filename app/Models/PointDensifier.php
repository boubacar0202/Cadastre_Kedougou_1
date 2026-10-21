<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;

class PointDensifier extends Model
{
    //
    use HasFactory; 

    protected $fillable = [ 
        'txt_nomPoint',
        'txt_dateCreationPoint',
        'slt_departement',
        'slt_arrondissement',
        'slt_commune',
        'txt_descriptionPoint',
        'txt_situationPoint',
        'txt_estPoint',
        'txt_nordPoint',
        'txt_hauteurPoint',
        'fichierPDFPoint'
    ];

    protected $appends = ['fichierPDF'];

    public function getFichierPDFAttribute()
    {
        return $this->attributes['fichierPDFPoint'] ?? null;
    }

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
