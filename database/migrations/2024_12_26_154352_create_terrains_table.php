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
            $table->string('txt_lotissement')->nullable();
            $table->string('txt_num_lotissement')->nullable();
            $table->string('txt_num_section')->nullable();
            $table->string('txt_num_parcelle')->nullable();
            $table->string('txt_num_titre')->nullable();
            $table->string('txt_titre_mere')->nullable();
            $table->decimal('nbr_surface', 10, 2)->nullable();
            $table->string('slt_document_admin')->nullable();
            $table->string('txt_num_deliberation')->nullable();
            $table->date('dt_date_deliberation')->nullable();
            $table->string('txt_nicad')->nullable()->unique();

            // Clés étrangères (Relations)
            $table->foreignId('slt_region')->constrained('regions')->onDelete('cascade');
            $table->foreignId('slt_departement')->constrained('departements')->onDelete('cascade');
            $table->foreignId('slt_arrondissement')->constrained('arrondissements')->onDelete('cascade');
            $table->foreignId('slt_commune')->constrained('communes')->onDelete('cascade');
            $table->foreignId('txt_num_dossier')->constrained('dossiers')->onDelete('cascade');

            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('terrains');
    }
};
