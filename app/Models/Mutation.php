<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;

class Mutation extends Model
{
    // 

    use HasFactory; 
    protected $fillable = [ 
        'txt_prenomMutation',
        'txt_nomMT',
        'txt_nationaliteMutation',
        'slt_civiliteMutation',
        'slt_pieceMutation',
        'txt_numPieceMutation',
        'dt_date_delivranceMutation',
        'dt_date_naissanceMutation',
        'txt_lieu_naissanceMutation',
        'txt_adresseMutation',
        'tel_telephoneMutation', 
        'txt_representantMutation',
        'tel_telRepresentantMutation',
        'txt_typeMutation',
        'txt_nomNotaireMutation', 
        'txt_temoinMutation',
        'dt_dateMutation',
        'txt_jugementMutation',
        'txt_observationMutation',
    ];

    public function terrain()
    {
        return $this->belongsTo(Terrain::class);
    }

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }

}
