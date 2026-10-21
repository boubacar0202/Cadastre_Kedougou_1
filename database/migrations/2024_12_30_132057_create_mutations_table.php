<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mutations')) {
            return;
        }

        Schema::create('mutations', function (Blueprint $table) {
            $table->id();

            // ============ Identification ============
            $table->text('txt_numeroMutation');
            $table->text('txt_num_dossier')->nullable();          // 🆕
            $table->string('txt_num_lotissementMutation');
            $table->string('txt_lotissementMutation');

            // ============ Titulaire ============
            $table->text('slt_titulaireMT');
            $table->text('txt_nationaliteMT')->nullable();
            $table->text('slt_civiliteMT')->nullable();
            $table->text('txt_prenomMT')->nullable();
            $table->text('txt_nomMT')->nullable();
            $table->text('slt_pieceMT')->nullable();
            $table->text('txt_numPieceMT')->nullable();
            $table->date('dt_date_delivranceMT')->nullable();
            $table->date('dt_date_naissanceMT')->nullable();
            $table->text('txt_lieu_naissanceMT')->nullable();
            $table->text('txt_adresseMT')->nullable();
            $table->text('tel_telephoneMT')->nullable();
            $table->text('txt_representantMT')->nullable();
            $table->text('tel_telRepresentantMT')->nullable();

            // Personne morale (titulaire)  ✅ 1 seul "s"
            $table->text('slt_categoriePM_MT')->nullable();
            $table->text('txt_formJuridiquePM_MT')->nullable();
            $table->text('txt_denominationPM_MT')->nullable();
            $table->text('txt_nineaPM_MT')->nullable();
            $table->text('txt_personneRepresentantPM_MT')->nullable();  // 🔧 FIX
            $table->text('txt_fonctionPM_MT')->nullable();
            $table->text('txt_telephonePM_MT')->nullable();
            $table->text('txt_emailPM_MT')->nullable();
            $table->text('txt_adressePM_MT')->nullable();

            // État (titulaire) — sans accent
            $table->text('slt_etablissementPA_MT')->nullable();
            $table->text('txt_personneResponsablePA_MT')->nullable();
            $table->text('txt_fonctionResponsablePA_MT')->nullable();
            $table->text('txt_telephonePA_MT')->nullable();
            $table->text('txt_emailPA_MT')->nullable();
            $table->text('txt_ministereTutelePA_MT')->nullable();

            // ============ Acquéreur ============
            $table->text('slt_titulaireMutation');
            $table->text('txt_prenomMutation')->nullable();
            $table->text('txt_nomMutation')->nullable();
            $table->text('txt_nationaliteMutation')->nullable();
            $table->text('slt_civiliteMutation')->nullable();
            $table->text('slt_pieceMutation')->nullable();
            $table->text('txt_numPieceMutation')->nullable();
            $table->date('dt_date_delivranceMutation')->nullable();
            $table->date('dt_date_naissanceMutation')->nullable();
            $table->text('txt_lieu_naissanceMutation')->nullable();
            $table->text('txt_adresseMutation')->nullable();
            $table->text('tel_telephoneMutation')->nullable();
            $table->text('txt_representantMutation')->nullable();
            $table->text('tel_telRepresentantMutation')->nullable();

            // Acte de mutation
            $table->text('txt_typeActeMutation');
            $table->text('txt_nomNotaireMutation')->nullable();
            $table->date('dt_dateMutation');
            $table->text('txt_natureMutation')->nullable();
            $table->text('txt_jugementMutation')->nullable();   // 🆕
            $table->text('txt_observationMutation')->nullable();

            // Personne morale (acquéreur) ✅ 1 seul "s"
            $table->text('slt_categoriePM_Mutation')->nullable();
            $table->text('txt_formJuridiquePM_Mutation')->nullable();
            $table->text('txt_denominationPM_Mutation')->nullable();
            $table->text('txt_nineaPM_Mutation')->nullable();
            $table->text('txt_personneRepresentantPM_Mutation')->nullable();  // 🔧 FIX
            $table->text('txt_fonctionPM_Mutation')->nullable();
            $table->text('txt_telephonePM_Mutation')->nullable();
            $table->text('txt_emailPM_Mutation')->nullable();
            $table->text('txt_adressePM_Mutation')->nullable();

            // État (acquéreur) — sans accent
            $table->text('slt_etablissementPA_Mutation')->nullable();
            $table->text('txt_personneResponsablePA_Mutation')->nullable();
            $table->text('txt_fonctionResponsablePA_Mutation')->nullable();
            $table->text('txt_telephonePA_Mutation')->nullable();
            $table->text('txt_emailPA_Mutation')->nullable();
            $table->text('txt_ministereTutelePA_Mutation')->nullable();

            // Fichier PDF (chemin de stockage)
            $table->text('fichierPDFMutation')->nullable();

            // ============ Clés étrangères ============
            // 🔧 RESTRICT au lieu de CASCADE pour éviter la suppression en cascade
            $table->foreignId('terrain_id')
                  ->nullable()
                  ->constrained('terrains')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreignId('dossier_id')
                  ->nullable()
                  ->constrained('dossiers')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->string('txt_nicadMutation')->nullable();

            $table->timestamps();

            // 🔍 Index pour les recherches fréquentes
            $table->index('txt_num_lotissementMutation');
            $table->index('txt_lotissementMutation');
            $table->index('txt_nicadMutation');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('mutations');
        Schema::enableForeignKeyConstraints();
    }
};