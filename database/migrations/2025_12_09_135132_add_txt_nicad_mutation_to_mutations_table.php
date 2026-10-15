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
        if (!Schema::hasColumn('mutations', 'txt_nicadMutation')) {
            Schema::table('mutations', function (Blueprint $table) {
                $table->string('txt_nicadMutation')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('mutations', function (Blueprint $table) {
            $table->dropColumn('txt_nicadMutation');
        });
    }

};
