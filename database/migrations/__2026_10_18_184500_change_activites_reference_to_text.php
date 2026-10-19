<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activites_utilisateurs') || ! Schema::hasColumn('activites_utilisateurs', 'reference')) {
            return;
        }

        Schema::table('activites_utilisateurs', function (Blueprint $table) {
            $table->text('reference')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('activites_utilisateurs') || ! Schema::hasColumn('activites_utilisateurs', 'reference')) {
            return;
        }

        Schema::table('activites_utilisateurs', function (Blueprint $table) {
            $table->string('reference', 120)->nullable()->change();
        });
    }
};
