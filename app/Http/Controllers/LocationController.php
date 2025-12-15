<?php

namespace App\Http\Controllers;

use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Departement;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
 
class LocationController extends Controller
{
    public function getDepartementsByRegion(string $regionId)
    {
        $departements = Departement::where('region_id', $regionId)->get();

        return response()->json([
            'departements' => $departements,
        ]);
    }

    public function getArrondissementByDepartement(string $id)
    {
        $arrondissements = Arrondissement::where('departement_id', $id)->get();

        return response()->json([
            'arrondissements' => $arrondissements,
        ]);
    }

    public function getCommunesByArrondissement(string $id)
    {
        $communes = Commune::where('arrondissement_id', $id)->get();

        return response()->json([
            'communes' => $communes,
        ]);
    }
}
