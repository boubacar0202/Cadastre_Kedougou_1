<?php

namespace App\Http\Controllers;

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
            'dossier',
            'titulaire',
            'references_cadastrales',
            'evaluations_batis',
        ];

        $query = Terrain::with($relations)
            ->whereNotNull('txt_nicad')
            ->where('txt_nicad', '!=', '');

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

        if ($request->boolean('export')) {
            return response()->json(
                $query->orderBy('id', 'desc')->get()
            );
        }

        $totalGeneral = Terrain::query()
            ->whereNotNull('txt_nicad')
            ->where('txt_nicad', '!=', '')
            ->count();

        $terrains = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render("matriceCadastrale/create", [
            'terrains' => $terrains,
            'totalGeneral' => $totalGeneral,
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