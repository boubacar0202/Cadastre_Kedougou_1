<?php

namespace App\Http\Controllers;

use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CodeAcces;    

class TerrainController extends Controller
{
    public function destroyTerrain(Request $request, $id)
    {
        try {

            if (!$request->filled('code')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code administrateur requis.'
                ], 422);
            }

            $code = CodeAcces::where('id', 3)
                ->where('code', $request->input('code'))
                ->first();

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code d’accès invalide.'
                ], 403);
            }

            $terrain = Terrain::findOrFail($id);

            DB::beginTransaction();

            $titulaire = $terrain->titulaire;

            if ($terrain->dossier) {
                $terrain->dossier->delete();
            }

            if ($terrain->references_usage) {
                $terrain->references_usage->delete();
            }

            if ($terrain->references_cadastrales) {
                $terrain->references_cadastrales->delete();
            }

            $terrain->delete();

            if ($titulaire && $titulaire->terrains()->count() === 0) {
                $titulaire->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Terrain supprimé avec succès.'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    }

 
}
 