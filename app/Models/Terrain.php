<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terrain extends Model
{
    //
    use HasFactory;
    protected $fillable = [

        'txt_num_dossier',
        'txt_lotissement',
        'txt_HorsLotissement',
        'txt_num_lotissement',
        'txt_num_section',
        'txt_num_parcelle', 
        'nbr_surface',
        'txt_titre_mere',
        'txt_num_titre',
        'txt_appartement',
        'slt_document_admin',
        'txt_num_deliberation',
        'dt_date_deliberation',
        'txt_nicad',
        'slt_region',
        'slt_departement',
        'slt_arrondissement',
        'slt_commune',
        'reference_cadastrale_id',
        'titulaire_id', 
    ];

    public function evaluations_cours_amenagees()
    {
        return $this->hasOne(EvaluationCoursAmenagee::class, 'txt_nicad', 'txt_nicad');
    }

    public function evaluations_amenagements()
    {
        return $this->hasOne(EvaluationAmenagement::class, 'txt_nicad', 'txt_nicad');
    }

    public function evaluations_clotures()
    {
        return $this->hasOne(EvaluationCloture::class, 'txt_nicad', 'txt_nicad');
    }

    public function evaluations_batis()
    {
        return $this->hasOne(EvaluationBati::class, 'txt_nicad', 'txt_nicad');
    }

    public function evaluations_terrains()
    {
        return $this->hasOne(EvaluationTerrain::class, 'txt_nicad', 'txt_nicad');
    } 

    public function references_usages()
    {
        return $this->hasMany(ReferenceUsage::class, 'txt_nicad', 'txt_nicad');
    }
 
    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'slt_region', 'id'); // Relation avec la table regions
    }

    public function departement() {
        return $this->belongsTo(Departement::class, 'slt_departement', 'id');
    }

    public function arrondissement() {
        return $this->belongsTo(Arrondissement::class, 'slt_arrondissement', 'id');
    }

    public function commune() {
        return $this->belongsTo(Commune::class, 'slt_commune', 'id');
    }
 
    public function titulaire()
    {
        return $this->belongsTo(Titulaire::class, 'titulaire_id');
    } 

    public function references_cadastrales()
    {
        return $this->belongsTo(ReferenceCadastrale::class, 'reference_cadastrale_id');
    }

    public function mutations()
    {
        return $this->hasMany(Mutation::class);
    }


 
}
