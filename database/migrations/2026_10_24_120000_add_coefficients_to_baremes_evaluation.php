<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baremes_evaluation', function (Blueprint $table) {
            $table->decimal('prix', 14, 4)->change();
        });

        $now = now();

        foreach ($this->lignes() as $ligne) {
            $exists = DB::table('baremes_evaluation')
                ->where('groupe', $ligne['groupe'])
                ->where('type', $ligne['type'])
                ->where('code', $ligne['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('baremes_evaluation')->insert([
                ...$ligne,
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('baremes_evaluation')
            ->whereIn('groupe', ['coefficient', 'taux_locatif'])
            ->delete();

        Schema::table('baremes_evaluation', function (Blueprint $table) {
            $table->unsignedInteger('prix')->change();
        });
    }

    private function lignes(): array
    {
        return [
            ['groupe' => 'coefficient', 'type' => '', 'code' => 'tva', 'prix' => 0.18, 'ordre' => 1],
            ['groupe' => 'coefficient', 'type' => '', 'code' => 'surface_utile', 'prix' => 0.78, 'ordre' => 2],
            ['groupe' => 'coefficient', 'type' => '', 'code' => 'terrain_bati', 'prix' => 0.5, 'ordre' => 3],
            ['groupe' => 'taux_locatif', 'type' => 'Maison individuelle', 'code' => '1', 'prix' => 0.1344, 'ordre' => 1],
            ['groupe' => 'taux_locatif', 'type' => 'Maison individuelle', 'code' => '2', 'prix' => 0.12, 'ordre' => 2],
            ['groupe' => 'taux_locatif', 'type' => 'Maison individuelle', 'code' => '3', 'prix' => 0.12, 'ordre' => 3],
            ['groupe' => 'taux_locatif', 'type' => 'Maison individuelle', 'code' => 'defaut', 'prix' => 0.1, 'ordre' => 99],
            ['groupe' => 'taux_locatif', 'type' => 'Immeuble collectif', 'code' => 'A', 'prix' => 0.1344, 'ordre' => 1],
            ['groupe' => 'taux_locatif', 'type' => 'Immeuble collectif', 'code' => 'B', 'prix' => 0.12, 'ordre' => 2],
            ['groupe' => 'taux_locatif', 'type' => 'Immeuble collectif', 'code' => 'C', 'prix' => 0.12, 'ordre' => 3],
            ['groupe' => 'taux_locatif', 'type' => 'Immeuble collectif', 'code' => 'defaut', 'prix' => 0.1, 'ordre' => 99],
        ];
    }
};
