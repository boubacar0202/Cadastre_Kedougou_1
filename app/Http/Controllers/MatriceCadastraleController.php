<?php

namespace App\Http\Controllers;

use App\Models\EvaluationBati;
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MatriceCadastraleController extends Controller
{
    public function index(Request $request)
    {
        return $this->create($request);
    }

    public function create(Request $request)
    {
        // Relations nécessaires pour l'affichage
        $relations = [
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
        ];

        $query = Terrain::with($relations);

        // Filtres
        if ($request->filled('numero')) {
            $query->whereHas('dossier', function($q) use ($request) {
                $q->where('txt_num_dossier', 'like', '%' . $request->numero . '%');
            });
        }

        if ($request->filled('nicad')) {
            $query->where('txt_nicad', 'like', '%' . $request->nicad . '%');
        }

        if ($request->filled('titulaire')) {
            $query->whereHas('titulaire', function($q) use ($request) {
                $q->where(DB::raw("CONCAT(txt_prenom, ' ', txt_nom, ' ', COALESCE(txt_denominationPM, ''))"), 
                    'like', '%' . $request->titulaire . '%');
            });
        }

        if ($request->filled('commune')) {
            $query->whereHas('commune', function($q) use ($request) {
                $q->where('slt_commune', 'like', '%' . $request->commune . '%');
            });
        }

        // TOUTES les lignes
        $terrains = $query->orderBy('id', 'desc')->get();

        // Récupération du terrain par NICAD (si présent en session)
        $txtNicad = session('txt_nicad');
        $terrain = null;
        if ($txtNicad) {
            $terrain = Terrain::with([
                'references_usages',
                'evaluations_terrains',
                'evaluations_clotures',
                'evaluations_amenagements',
                'evaluations_batis',
                'evaluations_cours_amenagees'
            ])->where('txt_nicad', $txtNicad)->first();
        }

        // Récupération des références d'usages et évaluations
        $referencesUsages = ReferenceUsage::with('terrain')->get();
        $evaluationsBatis = EvaluationBati::with('terrain')->get();

        return Inertia::render("matriceCadastrale/create", [
            'terrain' => $terrain,
            'terrains' => $terrains,
            'references_usages' => $referencesUsages,
            'evaluations_batis' => $evaluationsBatis,
            'filters' => $request->only(['numero', 'nicad', 'titulaire', 'commune'])
        ]);
    }

    public function destroy($id)
    {
        $terrain = Terrain::findOrFail($id);
        $terrain->delete();

        return redirect()->back()->with('success', 'Terrain supprimé avec succès');
    }
}