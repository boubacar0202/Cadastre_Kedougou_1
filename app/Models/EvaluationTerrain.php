<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationTerrain extends Model
{
    //
    protected $table = 'evaluations_terrains';
    protected $fillable = [
        'txt_nicad',
        'txt_num_dossier',
        'nbr_surface',
        'txt_date_devaluation',
        'txt_superficie_totale',
        'txt_superficie_bati_sol',
        'slt_secteur',
        'nbr_prix_metre_carre',
        'nbr_valeur_terrain',  
        'nbr_valeurVenaleLimeuble',
        'nbr_valeurLocative',
        'dt_dateEvaluation',
    ];

    
    public function terrain() {
        return $this->belongsTo(Terrain::class, 'txt_nicad', 'txt_nicad');
    }
    
    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'txt_num_dossier');
    }
    
}
