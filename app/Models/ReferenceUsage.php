<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceUsage extends Model
{
    //
    protected $table = 'references_usages';
    protected $fillable = [
        'txt_nicad',
        'txt_num_dossier',
        'slt_residence',
        'slt_usage',
        'txt_nomOccupantTG',
        'txt_numAppartementTG',
        'txt_activiteTG',
        'txt_nineaTG',
        'tel_telephoneTG',
        'nbr_montantLoyerTG',
        'txt_dateLieuNaissanceTG',
        'txt_cniPasseportTG',
        'dt_dateDelivranceTG',
        'nbr_montantLoyerTotal',
        'nbr_TVATotal',
    ];

 
    public function terrain() {
        return $this->belongsTo(Terrain::class, 'txt_nicad', 'txt_nicad');
    }

    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'txt_num_dossier');
    }
    
}
