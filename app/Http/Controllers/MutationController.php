<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Departement;
use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Dossier;
use App\Models\EvaluationAmenagement;
use App\Models\EvaluationBati;
use App\Models\EvaluationCloture;
use App\Models\EvaluationCoursAmenagee;
use App\Models\ReferenceCadastrale;
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use App\Models\Titulaire;
use Illuminate\Http\Request;
use Inertia\Inertia;
class MutationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $terrains = Terrain::all();

        return Inertia::render("mutation/create", [
            "terrains" => $terrains,  
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
