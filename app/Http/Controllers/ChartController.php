<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChartController extends Controller
{
    public function chart()
    {
        $pieData = [
            'labels' => ['Catégorie A', 'Catégorie B', 'Catégorie C'],
            'data' => [30, 45, 25]
        ];

        $barData = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
            'data' => [12, 19, 3, 5, 2, 3]
        ];

        $gridData = [
            ['mois' => 'Janvier', 'ventes' => 12, 'objectif' => 15],
            ['mois' => 'Février', 'ventes' => 19, 'objectif' => 20],
            ['mois' => 'Mars', 'ventes' => 3, 'objectif' => 18],
            ['mois' => 'Avril', 'ventes' => 5, 'objectif' => 16],
            ['mois' => 'Mai', 'ventes' => 2, 'objectif' => 14],
            ['mois' => 'Juin', 'ventes' => 3, 'objectif' => 17]
        ];

        return inertia('Dashboard', [
            'pieData' => $pieData,
            'barData' => $barData,
            'gridData' => $gridData
        ]);
    }

    public function getChartData()
    {
        // Données dynamiques depuis la base
        $pieData = [
            'labels' => ['Catégorie A', 'Catégorie B', 'Catégorie C'],
            'data' => [30, 45, 25]
        ];

        $barData = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
            'data' => [12, 19, 3, 5, 2, 3]
        ];

        return response()->json([
            'pie' => $pieData,
            'bar' => $barData
        ]);
    }
}