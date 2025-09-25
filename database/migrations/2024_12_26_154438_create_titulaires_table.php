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
        Schema::create('titulaires', function (Blueprint $table) {
            $table->id();
            $table->string('slt_titulaire');
            $table->string('txt_nationalite');
            $table->string('slt_civilite');
            $table->string('txt_prenom');
            $table->string('txt_nom');
            $table->string('slt_piece');
            $table->string('txt_numPiece');
            $table->date('dt_date_delivrance');
            $table->date('dt_date_naissance');
            $table->string('txt_lieu_naissance');
            $table->string('txt_adresse');
            $table->string('tel_telephone');
            $table->string('eml_email')->nullable();
            $table->string('txt_representant')->nullable();
            $table->string('tel_telRepresentant')->nullable();
                // Personne physique
            $table->string('slt_categoriePM')->nullable();
            $table->string('txt_formJuridiquePM')->nullable();
            $table->string('txt_denominationPM')->nullable(); 
            $table->string('txt_nineaPM')->nullable(); 
            $table->string('txt_perssonneRepresentantPM')->nullable(); 
            $table->string('txt_fonctionPM')->nullable(); 
            $table->string('txt_telephonePM')->nullable(); 
            $table->string('txt_emailPM')->nullable(); 
            $table->string('txt_adressePM')->nullable(); 
                // Personne morale
            $table->string('slt_etablissementPA')->nullable();
            $table->string('txt_personneResponsablePA')->nullable();
            $table->string('txt_fonctionResponsablePA')->nullable();
            $table->string('txt_telephonePA')->nullable();
            $table->string('txt_emailPA')->nullable();
            $table->string('txt_ministèreTutelePA')->nullable();

            $table->string('fichierPDF')->nullable();

            /* les champs ajouters */

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titulaires');
    }
};
