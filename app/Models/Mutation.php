<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'txt_numeroMutation',
        'txt_nicadMutation',
        'txt_num_dossier',                  // 🆕
        'txt_num_lotissementMutation',
        'txt_lotissementMutation',

        // ---- Titulaire ----
        'slt_titulaireMT',
        'txt_nationaliteMT',
        'slt_civiliteMT',
        'txt_prenomMT',
        'txt_nomMT',
        'slt_pieceMT',
        'txt_numPieceMT',
        'dt_date_delivranceMT',
        'dt_date_naissanceMT',
        'txt_lieu_naissanceMT',
        'txt_adresseMT',
        'tel_telephoneMT',
        'txt_representantMT',
        'tel_telRepresentantMT',

        // Personne morale (titulaire) — NOM CORRIGÉ (1 seul "s")
        'slt_categoriePM_MT',
        'txt_formJuridiquePM_MT',
        'txt_denominationPM_MT',
        'txt_nineaPM_MT',
        'txt_personneRepresentantPM_MT',    // 🔧
        'txt_fonctionPM_MT',
        'txt_telephonePM_MT',
        'txt_emailPM_MT',
        'txt_adressePM_MT',

        // État (titulaire) — NOM SANS ACCENT
        'slt_etablissementPA_MT',
        'txt_personneResponsablePA_MT',
        'txt_fonctionResponsablePA_MT',
        'txt_telephonePA_MT',
        'txt_emailPA_MT',
        'txt_ministereTutelePA_MT',         // 🔧 (sans accent)

        // ---- Acquéreur ----
        'slt_titulaireMutation',
        'txt_prenomMutation',
        'txt_nomMutation',
        'txt_nationaliteMutation',
        'slt_civiliteMutation',
        'slt_pieceMutation',
        'txt_numPieceMutation',
        'dt_date_delivranceMutation',
        'dt_date_naissanceMutation',
        'txt_lieu_naissanceMutation',
        'txt_adresseMutation',
        'tel_telephoneMutation',
        'txt_representantMutation',
        'tel_telRepresentantMutation',      // (une seule fois)

        // Acte
        'txt_typeActeMutation',
        'txt_nomNotaireMutation',
        'dt_dateMutation',
        'txt_natureMutation',
        'txt_jugementMutation',             // 🆕
        'txt_observationMutation',

        // Personne morale (acquéreur) — NOM CORRIGÉ
        'slt_categoriePM_Mutation',
        'txt_formJuridiquePM_Mutation',
        'txt_denominationPM_Mutation',
        'txt_nineaPM_Mutation',
        'txt_personneRepresentantPM_Mutation',   // 🔧
        'txt_fonctionPM_Mutation',
        'txt_telephonePM_Mutation',
        'txt_emailPM_Mutation',
        'txt_adressePM_Mutation',

        // État (acquéreur) — NOM SANS ACCENT
        'slt_etablissementPA_Mutation',
        'txt_personneResponsablePA_Mutation',
        'txt_fonctionResponsablePA_Mutation',
        'txt_telephonePA_Mutation',
        'txt_emailPA_Mutation',
        'txt_ministereTutelePA_Mutation',        // 🔧

        // Fichier
        'fichierPDFMutation',
    ];

    /**
     * Casts pour les dates et types
     */
    protected $casts = [
        'dt_date_delivranceMT'        => 'date',
        'dt_date_naissanceMT'         => 'date',
        'dt_date_delivranceMutation'  => 'date',
        'dt_date_naissanceMutation'   => 'date',
        'dt_dateMutation'             => 'date',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }

    public function terrain()
    {
        return $this->belongsTo(Terrain::class, 'terrain_id');
    }
}