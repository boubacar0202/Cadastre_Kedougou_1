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
        Schema::create('point_densifiers', function (Blueprint $table) {
            $table->id();

            $table->string('txt_nomPoint');
            $table->string('txt_dateCreationPoint');
            $table->text('txt_descriptionPoint')->nullable();
            $table->text('txt_situationPoint')->nullable();
            $table->string('txt_estPoint')->nullable();
            $table->string('txt_nordPoint')->nullable();
            $table->string('txt_hauteurPoint')->nullable();
            $table->text('fichierPDFPoint')->nullable();

            $table->foreignId('slt_departement')->constrained('departements');
            $table->foreignId('slt_arrondissement')->constrained('arrondissements');
            $table->foreignId('slt_commune')->constrained('communes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('point_densifiers');
    }
};
