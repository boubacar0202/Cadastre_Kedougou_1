<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('baremes_evaluation')) {
            return;
        }

        Schema::create('baremes_evaluation', function (Blueprint $table) {
            $table->id();
            $table->string('groupe');
            $table->string('type')->default('');
            $table->string('code');
            $table->unsignedInteger('prix');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['groupe', 'type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baremes_evaluation');
    }
};
