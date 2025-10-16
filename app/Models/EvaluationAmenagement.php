<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationAmenagement extends Model
{
    //
    use HasFactory;
    protected $table = 'evaluations_amenagements';
    protected $fillable = [
        'txt_num_dossier',
        'txt_nicad',
        'txt_designation_am',
        'nbr_valeur_unitaire_am',
        'nbr_quantile_am',
        'slt_coeficien_am',
        'nbr_valeur_am',
        'nbr_valeur_totale_ap',
    ];

    public function terrain() {
        return $this->belongsTo(Terrain::class, 'txt_nicad', 'txt_nicad');
    }

    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'txt_num_dossier');
    }


}
