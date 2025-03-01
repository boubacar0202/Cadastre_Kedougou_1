<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dossier extends Model
{

    use HasFactory;



    protected $fillable = [
        "txt_num_dossier",
        "txt_num_dordre",
        "slt_service_rendu",
        "txt_etat_cession",
        "txt_cession_definitive",
        "dt_date_creation",
    ];
    protected $casts = [
        'dt_date_creation' => 'date',
    ];


    public function terrains()
    {
        return $this->hasMany(Terrain::class);
    }


}
