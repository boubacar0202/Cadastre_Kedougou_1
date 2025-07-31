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
        Schema::create('references_cadastrales', function (Blueprint $table) {

            $table->id();
            $table->string('rd_immatriculation_terrain');
            $table->string('slt_dependant_domaine')->nullable();
            $table->string('ussu_bornage')->nullable();  
            $table->string('slt_lf')->nullable();
            $table->string('txt_num_requisition')->nullable();
            $table->string('txt_surface_bornage')->nullable();
            $table->string('dt_date_bornage')->nullable();
            $table->string('txt_nom_geometre')->nullable();
 
            $table->timestamps();
 
        });
    }

 
    public function down(): void
    {
        Schema::dropIfExists('references_cadastrales');
    }
};
