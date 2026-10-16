<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shapefiles', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('nom_table')->unique();     // ✅ unicité
            $table->integer('srid')->default(4326);    // ✅ entier (ou gardez string)
            $table->integer('nb_entites')->default(0);
            $table->json('bbox')->nullable();
            $table->string('couleur')->default('#3388ff');
            $table->boolean('visible')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // ✅ null si user supprimé
            $table->string('type_entite')->nullable();

            $table->index('visible');                  // ✅ index (optionnel)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shapefiles');
        
    }
};