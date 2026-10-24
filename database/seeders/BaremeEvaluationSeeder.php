<?php

namespace Database\Seeders;

use App\Models\BaremeEvaluation;
use Illuminate\Database\Seeder;

class BaremeEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->lignes() as $ligne) {
            BaremeEvaluation::updateOrCreate(
                [
                    'groupe' => $ligne['groupe'],
                    'type' => $ligne['type'],
                    'code' => $ligne['code'],
                ],
                [
                    'prix' => $ligne['prix'],
                    'ordre' => $ligne['ordre'],
                    'actif' => true,
                ]
            );
        }
    }

    private function lignes(): array
    {
        $maison = [
            '1' => 205891, '2' => 190883, '3' => 180937, '4' => 158411,
            '5' => 119942, '6' => 88201, '7' => 58801, '8' => 40663,
            '9' => 11117, '10' => 10000, '11' => 8000,
        ];
        $immeuble = [
            'A' => 244257, 'B' => 226543, 'C' => 204069, 'D' => 177171,
            'E' => 142293, 'F' => 123552, 'G' => 93878, 'H' => 61602,
            'I' => 53793, 'J' => 25000, 'K' => 22000, 'L' => 20000, 'M' => 8000,
        ];
        $secteurs = ['1' => 3000, '2' => 2000, '3' => 1000];
        $cours = ['1' => 17500, '2' => 13500, '3' => 10500, '4' => 5000];
        $clotures = ['1' => 48269, '2' => 35105, '3' => 28084, '4' => 25744, '5' => 11848, '6' => 4209, '7' => 853];

        $coefficients = [
            'tva' => 0.18,
            'surface_utile' => 0.78,
            'terrain_bati' => 0.5,
        ];
        $tauxMaison = ['1' => 0.1344, '2' => 0.12, '3' => 0.12, 'defaut' => 0.1];
        $tauxImmeuble = ['A' => 0.1344, 'B' => 0.12, 'C' => 0.12, 'defaut' => 0.1];

        return array_merge(
            $this->groupe('batiment', 'Maison individuelle', $maison),
            $this->groupe('batiment', 'Immeuble collectif', $immeuble),
            $this->groupe('secteur', '', $secteurs),
            $this->groupe('cours_amenagee', '', $cours),
            $this->groupe('cloture', '', $clotures),
            $this->groupe('coefficient', '', $coefficients),
            $this->groupe('taux_locatif', 'Maison individuelle', $tauxMaison),
            $this->groupe('taux_locatif', 'Immeuble collectif', $tauxImmeuble),
        );
    }

    private function groupe(string $groupe, string $type, array $prix): array
    {
        $lignes = [];
        $ordre = 1;

        foreach ($prix as $code => $montant) {
            $lignes[] = [
                'groupe' => $groupe,
                'type' => $type,
                'code' => (string) $code,
                'prix' => $montant,
                'ordre' => $ordre,
            ];
            $ordre++;
        }

        return $lignes;
    }
}
