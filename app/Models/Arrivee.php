<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arrivee extends Model
{
    use HasFactory, SoftDeletes;
    // Nom de la table (si Laravel ne devine pas automatiquement)
    protected $table = 'arrivees';
    protected $fillable = [
        'txt_numdordre', 
        'txt_numbordereau',
        'txt_numdossier',
        'txt_caractere',
        'txt_typecourrier',
        'dt_datearrivee',
        'txt_numcourier',
        'dt_datecourier',
        'txt_reference',
        'txt_categorie',
        'txt_designation',
        'txt_prenomNom',
        'txt_numLot',
        'txt_superficie',
        'txt_numTF', 
        'txt_lotissement',
        'dt_date',
        'txt_heure',
        'txt_lieu',
        'txt_nombrepiece',
        'txt_objet',
        'txt_expediteur',
        'txt_agenttraiteur',
        'txt_observation', 
        'fichierPDF',
    ];
       
}
