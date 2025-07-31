<?php

use App\Models\Region;
use App\Models\Terrain;
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
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('txt_num_dossier')->unique();
            $table->index('txt_num_dossier');
            $table->integer('txt_num_dordre')->unique();
            $table->string('slt_service_rendu')->nullable();
            $table->string('txt_etat_cession')->nullable();
            $table->string('txt_cession_definitive')->nullable();
            $table->date('dt_date_creation');
            $table->timestamps();
        });
    }

    public function down()
    {
        // Supprimer la table entière
        Schema::dropIfExists('dossiers');
       
    }

};
