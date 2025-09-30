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
                // Vérifier si le code existe et n’est pas encore utilisé
                $code = \App\Models\CodeAcces::where('code', $request->code)
                    ->lockForUpdate()
                    ->where('utilise', false)
                    ->first();

                if (!$code) {
                    throw new \InvalidArgumentException('❌ Code invalide ou déjà utilisé.');
                }

                // Récupérer le terrain
                $terrain = \App\Models\Terrain::findOrFail($request->terrain_id);

                // Suppression (choisis delete() si SoftDeletes, sinon forceDelete())
                $terrain->delete();


                // Marquer le code comme utilisé
                // $code->update(['utilise' => true]);
            });

            return response()->json([
                'success' => true,
                'message' => '✅ Terrain supprimé avec succès.',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation (Laravel le gère déjà, mais par sécurité)
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Terrain introuvable
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\InvalidArgumentException $e) {
            // Code invalide
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            // Autres erreurs (base de données, transaction, etc.)
            return response()->json([
                'success' => false,
                'message' => '⚠️ Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }





}
