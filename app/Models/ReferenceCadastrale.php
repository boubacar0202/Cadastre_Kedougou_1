<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceCadastrale extends Model
{
    use HasFactory;
    protected $table = 'references_cadastrales'; 
    protected $fillable = [
        'rd_immatriculation_terrain',
        'slt_dependant_domaine', 
        'ussu_bornage', 
        'slt_lf',
        'txt_num_requisition',
        'txt_surface_bornage',
        'dt_date_bornage',
        'txt_nom_geometre',
    ];

    public function terrains()
    {
        return $this->hasMany(Terrain::class);
    }

}
