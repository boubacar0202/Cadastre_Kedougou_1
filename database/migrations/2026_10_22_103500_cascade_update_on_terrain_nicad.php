<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $liens = [
        ['evaluations_amenagements', 'txt_nicad', 'evaluations_amenagements_txt_nicad_foreign'],
        ['evaluations_batis', 'txt_nicad', 'evaluations_batis_txt_nicad_foreign'],
        ['evaluations_clotures', 'txt_nicad', 'evaluations_clotures_txt_nicad_foreign'],
        ['evaluations_cours_amenagees', 'txt_nicad', 'evaluations_cours_amenagees_txt_nicad_foreign'],
        ['evaluations_terrains', 'txt_nicad', 'evaluations_terrains_txt_nicad_foreign'],
        ['references_usages', 'txt_nicad', 'references_usages_txt_nicad_foreign'],
        ['mutations', 'txt_nicadMutation', 'mutations_txt_nicadmutation_foreign'],
    ];

    public function up(): void
    {
        foreach ($this->liens as [$table, $colonne, $contrainte]) {
            $this->redefinir($table, $colonne, $contrainte, 'cascade');
        }
    }

    public function down(): void
    {
        foreach ($this->liens as [$table, $colonne, $contrainte]) {
            $this->redefinir($table, $colonne, $contrainte, 'restrict');
        }
    }

    private function redefinir(string $table, string $colonne, string $contrainte, string $onUpdate): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $colonne) || ! $this->contrainteExiste($table, $contrainte)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($colonne, $contrainte, $onUpdate) {
            $blueprint->dropForeign($contrainte);
            $blueprint->foreign($colonne, $contrainte)
                ->references('txt_nicad')
                ->on('terrains')
                ->onUpdate($onUpdate)
                ->onDelete('cascade');
        });
    }

    private function contrainteExiste(string $table, string $contrainte): bool
    {
        $ligne = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $contrainte, 'FOREIGN KEY']
        );

        return $ligne !== null;
    }
};
