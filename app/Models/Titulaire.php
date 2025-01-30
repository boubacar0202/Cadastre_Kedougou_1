<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Titulaire extends Model
{
    //
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
        'txt_ninea',
        'eml_email',
        'txt_representant',
        'tel_telRepresentant',
        ];
}
