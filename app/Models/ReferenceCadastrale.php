<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceCadastrale extends Model
{
<<<<<<< HEAD
    //
    protected $table = 'references_cadastrales';
    protected $fillable = [
        'rd_immatriculation_terrain',
        'slt_dependant_domaine',
=======
    protected $fillable = [
        'rd_immatriculation_terrain',
        'slt_dependant_domaine',
        'slt_bornage',
>>>>>>> f532143f9baf5b101836f86b9efee2216f2d363d
        'ussu_bornage',
        'txt_titre_mere',
        'slt_lf',
        'txt_num_requisition',
        'txt_surface_bornage',
        'dt_date_bornage',
        'txt_nom_geometre',
    ];
}
