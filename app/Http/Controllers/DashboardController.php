<?php

namespace App\Http\Controllers;

use App\Models\Terrain;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Récupérer tous les TERRAINS 
        $Delimitation = Terrain::whereNotNull('slt_document_admin')
                        ->where('slt_document_admin', '=', 'Déliberation')
                        ->count();

        // Récupérer tous les NICAD
        $Nicad = Terrain::whereNotNull('txt_nicad')
                        ->where('txt_nicad', '!=', '')
                        ->count();

        $SansNicad = Terrain::whereNull('txt_nicad')
                ->orWhere('txt_nicad', '=', '')
                ->count();


        return Inertia::render('Dashboard', [
            'totalTerrains' => Terrain::count(),
            'totalDelimitation' => $Delimitation,
            'totalNicad' => $Nicad,
            'totalSansNicad' => $SansNicad
        ]);
    }
}