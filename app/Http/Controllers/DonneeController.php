<?php

namespace App\Http\Controllers;

use App\Models\EvaluationBati;
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use Inertia\Inertia;

class DonneeController extends Controller
{
    //
    public function index(){
        return Inertia::render("donnee/create");
    }
    public function create()
    {
        $terrains = Terrain::with([
            'region',
            'departement',
            'commune',
            'arrondissement',
            'dossier',
            'titulaire',
            'references_cadastrales',
            'references_usages',
            'evaluations_terrains',
            'evaluations_clotures', 
            'evaluations_amenagements', 
            'evaluations_batis', 
            'evaluations_cours_amenagees'
        ])->get();
    
        $txtNicad = session('txt_nicad');

        $terrain = Terrain::with(['references_usages', 'evaluations_terrains', 'evaluations_clotures', 'evaluations_amenagements', 'evaluations_batis', 'evaluations_cours_amenagees'])
            ->where('txt_nicad', $txtNicad)
            ->first();
    
        return Inertia::render('donnee/create', [
            'terrain' => $terrain,
            'terrains' => $terrains,
            'references_usages' => ReferenceUsage::with('terrain')->get(), 
            'evaluations_batis' => EvaluationBati::with('terrain')->get(), 
        ]);
    }




}
