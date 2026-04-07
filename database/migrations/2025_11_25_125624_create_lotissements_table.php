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
        Schema::create('lotissements', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('txt_denomination'); 
            $table->string('txt_nombreParcelle');
            $table->string('txt_surfaceLotissement', 15, 2);
            $table->string('txt_documentsAfferants')->nullable();

            $table->foreignId('slt_departement')->constrained('departements')->onDelete('cascade');
            $table->foreignId('slt_arrondissement')->constrained('arrondissements')->onDelete('cascade');
            $table->foreignId('slt_commune')->constrained('communes')->onDelete('cascade');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotissements');
    }
};
