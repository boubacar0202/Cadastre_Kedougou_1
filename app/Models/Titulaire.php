<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Titulaire extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'slt_titulaire',
        'txt_nationalite',
        'slt_civilite',
        'txt_prenom',
        'txt_nom',
        'slt_piece',
        'txt_numPiece',
        'dt_date_delivrance',
        'dt_date_naissance',
        'txt_lieu_naissance',
        'txt_adresse',
        'tel_telephone', 
        'eml_email',
        'txt_representant',
        'tel_telRepresentant',
            // Personne physique
        'slt_categoriePM',
        'txt_formJuridiquePM', 
        'txt_denominationPM', 
        'txt_nineaPM', 
        'txt_perssonneRepresentantPM', 
        'txt_fonctionPM', 
        'txt_telephonePM', 
        'txt_emailPM', 
        'txt_adressePM', 
            // Personne morale
        'slt_etablissementPA',
        'txt_personneResponsablePA',
        'txt_fonctionResponsablePA',
        'txt_telephonePA',
        'txt_emailPA',
        'txt_ministèreTutelePA',

        'fichierPDF',
    ];

    public function terrains()
    {
        return $this->hasMany(Terrain::class);
    }

}
