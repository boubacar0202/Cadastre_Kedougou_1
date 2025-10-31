<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;

class Mutation extends Model
{
    // 

    use HasFactory; 

    protected $fillable = [ 
        'txt_numeroMutation',
        'txt_nicadMutation',
        // information titulaire
        'slt_titulaireMT',
        'txt_nationaliteMT',
        'slt_civiliteMT',
        'txt_prenomMT',
        'txt_nomMT',
        'slt_pieceMT',
        'txt_numPieceMT',
        'dt_date_delivranceMT',
        'dt_date_naissanceMT',
        'txt_lieu_naissanceMT',
        'txt_adresseMT',
        'tel_telephoneMT',  
        'txt_representantMT',
        'tel_telRepresentantMT', 
                // Personne physique
        'slt_categoriePM_MT',
        'txt_formJuridiquePM_MT', 
        'txt_denominationPM_MT', 
        'txt_nineaPM_MT', 
        'txt_perssonneRepresentantPM_MT', 
        'txt_fonctionPM_MT', 
        'txt_telephonePM_MT:', 
        'txt_emailPM_MT', 
        'txt_adressePM_MT', 
            // Personne morale
        'slt_etablissementPA_MT',
        'txt_personneResponsablePA_MT',
        'txt_fonctionResponsablePA_MT',
        'txt_telephonePA_MT',
        'txt_emailPA_MT',
        'txt_ministèreTutelePA_MT',
        // information ecquereur 
        'slt_titulaireMutation',
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
        'txt_typeActeMutation',
        'txt_nomNotaireMutation',
        'dt_dateMutation',
        'txt_jugementMutation',
        'txt_observationMutation',
            // Personne physique
        'slt_categoriePM_Mutation',
        'txt_formJuridiquePM_Mutation',
        'txt_denominationPM_Mutation',
        'txt_nineaPM_Mutation',
        'txt_perssonneRepresentantPM_Mutation',
        'txt_fonctionPM_Mutation',
        'txt_telephonePM_Mutation',
        'txt_emailPM_Mutation',
        'txt_adressePM_Mutation',
            // Personne morale
        'slt_etablissementPA_MT',
        'txt_personneResponsablePA_Mutation',
        'txt_fonctionResponsablePA_Mutation',
        'txt_telephonePA_Mutation',
        'txt_emailPA_Mutation',
        'txt_ministèreTutelePA_Mutation',

        'fichierPDFMutation',
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
