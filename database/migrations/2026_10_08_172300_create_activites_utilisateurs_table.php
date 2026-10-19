<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activites_utilisateurs')) {
            return;
        }

        Schema::create('activites_utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('methode', 10)->nullable();
            $table->string('module', 64)->nullable();
            $table->string('chemin', 255)->nullable();
            $table->string('libelle', 160)->nullable();
            $table->text('reference')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activites_utilisateurs');
    }
};
