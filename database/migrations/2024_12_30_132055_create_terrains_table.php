<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('terrains', function (Blueprint $table) {
            $table->id();
            $table->string('txt_num_parcelle')->nullable(); 
            $table->string('txt_lotissement')->nullable();
            $table->string('txt_HorsLotissement')->nullable();
            $table->string('txt_num_lotissement')->nullable();
            $table->string('txt_num_section')->nullable(); 
            $table->decimal('nbr_surface', 10, 2)->nullable(); 
            $table->string('txt_titre_mere')->nullable();
            $table->string('txt_num_titre')->nullable();
            $table->string('txt_appartement')->nullable();
            $table->string('slt_document_admin')->nullable();
            $table->string('txt_num_deliberation',15)->nullable();
            $table->date('dt_date_deliberation')->nullable();
            $table->string('txt_nicad')->nullable()->unique();  

            // Clés étrangères (Relations)
            $table->foreignId('slt_region')->constrained('regions')->onDelete('cascade');
            $table->foreignId('slt_departement')->constrained('departements')->onDelete('cascade');
            $table->foreignId('slt_arrondissement')->constrained('arrondissements')->onDelete('cascade');
            $table->foreignId('slt_commune')->constrained('communes')->onDelete('cascade');
            $table->foreignId('txt_num_dossier')->constrained('dossiers')->onDelete('cascade');
            // Correction des clés étrangères

            $table->foreignId('reference_cadastrale_id')->constrained('references_cadastrales')->onDelete('cascade');
            $table->foreignId('titulaire_id')->constrained('titulaires')->onDelete('cascade');
            
            $table->timestamps();
            
        });
    }


    public function down()
    {
        Schema::dropIfExists('terrains');
    }
    
};
