<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mutations', function (Blueprint $table) {
            $table->id();

            $table->string('txt_numeroMutation');
            // information titulaire
            $table->string('slt_titulaireMT');
            $table->string('txt_nationaliteMT')->nullable();
            $table->string('slt_civiliteMT')->nullable();
            $table->string('txt_prenomMT')->nullable();
            $table->string('txt_nomMT')->nullable();
            $table->string('slt_pieceMT')->nullable();
            $table->string('txt_numPieceMT')->nullable();
            $table->date('dt_date_delivranceMT')->nullable();
            $table->date('dt_date_naissanceMT')->nullable();
            $table->string('txt_lieu_naissanceMT')->nullable();
            $table->string('txt_adresseMT')->nullable();
            $table->string('tel_telephoneMT')->nullable(); 
            $table->string('txt_representantMT')->nullable()->nullable();
            $table->string('tel_telRepresentantMT')->nullable()->nullable();
                // Personne physique
            $table->string('slt_categoriePM_MT')->nullable();
            $table->string('txt_formJuridiquePM_MT')->nullable(); 
            $table->string('txt_denominationPM_MT')->nullable(); 
            $table->string('txt_nineaPM_MT')->nullable(); 
            $table->string('txt_perssonneRepresentantPM_MT')->nullable(); 
            $table->string('txt_fonctionPM_MT')->nullable(); 
            $table->string('txt_telephonePM_MT')->nullable(); 
            $table->string('txt_emailPM_MT')->nullable(); 
            $table->string('txt_adressePM_MT')->nullable(); 
                // Personne morale
            $table->string('slt_etablissementPA_MT')->nullable();
            $table->string('txt_personneResponsablePA_MT')->nullable();
            $table->string('txt_fonctionResponsablePA_MT')->nullable();
            $table->string('txt_telephonePA_MT')->nullable();
            $table->string('txt_emailPA_MT')->nullable();
            $table->string('txt_ministèreTutelePA_MT')->nullable();
            // information ecquereur 
            $table->string('slt_titulaireMutation');
            $table->string('txt_prenomMutation')->nullable();
            $table->string('txt_nomMT')->nullable();
            $table->string('txt_nationaliteMutation')->nullable();
            $table->string('slt_civiliteMutation')->nullable();
            $table->string('slt_pieceMutation')->nullable();
            $table->string('txt_numPieceMutation')->nullable();
            $table->date('dt_date_delivranceMutation')->nullable();
            $table->date('dt_date_naissanceMutation')->nullable();
            $table->string('txt_lieu_naissanceMutation')->nullable();
            $table->string('txt_adresseMutation')->nullable();
            $table->string('tel_telephoneMutation')->nullable();  
            $table->string('txt_typeActeMutation');
            $table->string('txt_nomNotaireMutation')->nullable();
            $table->date('dt_dateMutation');
            $table->string('txt_jugementMutation')->nullable();
            $table->string('txt_observationMutation')->nullable();
                // Personne physique
            $table->string('slt_categoriePM_Mutation')->nullable();
            $table->string('txt_formJuridiquePM_Mutation')->nullable();
            $table->string('txt_denominationPM_Mutation')->nullable();
            $table->string('txt_nineaPM_Mutation')->nullable();
            $table->string('txt_perssonneRepresentantPM_Mutation')->nullable();
            $table->string('txt_fonctionPM_Mutation')->nullable();
            $table->string('txt_telephonePM_Mutation')->nullable();
            $table->string('txt_emailPM_Mutation')->nullable();
            $table->string('txt_adressePM_Mutation')->nullable();
                // Personne morale
            $table->string('slt_etablissementPA_MT')->nullable();
            $table->string('txt_personneResponsablePA_Mutation')->nullable();
            $table->string('txt_fonctionResponsablePA_Mutation')->nullable();
            $table->string('txt_telephonePA_Mutation')->nullable();
            $table->string('txt_emailPA_Mutation')->nullable();
            $table->string('txt_ministèreTutelePA_Mutation')->nullable();
            

            $table->string('fichierPDFMutation')->nullable();
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutations');
    }
};
