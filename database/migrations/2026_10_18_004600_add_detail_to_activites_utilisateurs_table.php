<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activites_utilisateurs') || Schema::hasColumn('activites_utilisateurs', 'libelle')) {
            return;
        }

        Schema::table('activites_utilisateurs', function (Blueprint $table) {
            $table->string('methode', 10)->nullable()->after('action');
            $table->string('module', 64)->nullable()->after('methode');
            $table->string('chemin', 255)->nullable()->after('module');
            $table->string('libelle', 160)->nullable()->after('chemin');
            $table->string('reference', 120)->nullable()->after('libelle');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('activites_utilisateurs') || ! Schema::hasColumn('activites_utilisateurs', 'libelle')) {
            return;
        }

        Schema::table('activites_utilisateurs', function (Blueprint $table) {
            $table->dropColumn(['methode', 'module', 'chemin', 'libelle', 'reference']);
        });
    }
};
