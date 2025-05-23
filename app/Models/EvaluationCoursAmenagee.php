<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationCoursAmenagee extends Model
{
    //
    protected $fillable = [
        'chk_cours_amenager_totale',
        'nbr_surface_ca_total',
        'slt_categorie_ca_total',
        'nbr_prix_metre_carre_ca_total',
        'nbr_coefficient_ca_total',
        'nbr_valeur_ca_total',
        'nbr_valeur_total_cours',
        'txt_num_parcelle',
        'txt_num_dossier',
    ];

    // public function terrain()
    // {
    //     return $this->belongsTo(Terrain::class);
    // }

    
    public function terrain() {
        return $this->belongsTo(Terrain::class, 'txt_num_parcelle', 'id');
    }

    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'id');
    }
    
}
