<?php

namespace App\Http\Controllers;

use App\Models\EvaluationBati;
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use Inertia\Inertia;
use App\Models\CodeAcces; 
use Illuminate\Http\Request;     
use Illuminate\Support\Facades\DB;

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
 
  
    public function verifierEtSupprimer(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'terrain_id' => 'required|integer|exists:terrains,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $code = \App\Models\CodeAcces::where('code', $request->code)
                    ->where('utilise', false)
                    ->lockForUpdate()
                    ->first();

                if (!$code) {
                    // lancer une exception pour sortir de la transaction
                    throw new \Exception('❌ Votre Code est invalide !');
                }

                $terrain = \App\Models\Terrain::findOrFail($request->terrain_id);

                // supprime définitivement
                $terrain->forceDelete();

                // marquer le code comme utilisé
                $code->update(['utilise' => true]);
            });

            return response()->json([
                'success' => true,
                'message' => '✅ Terrain supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }




}
