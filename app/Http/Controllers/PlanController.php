<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\PointDensifier;
use App\Models\Shapefile;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Page principale avec le plan
     */
    public function index()
    {
        return Inertia::render('plan/create', [
            'point_densifiers' => PointDensifier::with([
                'departement', 'arrondissement', 'commune'
            ])->get(),
            'shapefiles' => Shapefile::latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $points = PointDensifier::with(['departement', 'arrondissement', 'commune'])->get();
        $departements = Departement::all();

        return Inertia::render('plan/create', [
            'point_densifiers' => $points,
            'departements'     => $departements,
            'arrondissements'  => [],
            'communes'         => [],
            'shapefiles'       => Shapefile::latest()->get(),
        ]);
    }
}