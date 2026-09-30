<?php

namespace App\Http\Controllers;

use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CodeAcces;  
use Inertia\Inertia;     

class TerrainController extends Controller
{
    public function destroyTerrain(Request $request, $id)
    {
        try {
            // 🔐 Vérification du code administrateur
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
                    'message' => 'Code d\'accès invalide.'
                ], 403);
            }

            DB::beginTransaction();

            $terrain = Terrain::findOrFail($id);
            $titulaire = $terrain->titulaire;

            // ✅ Suppression de la référence cadastrale (relation directe)
            if ($terrain->referenceCadastrale) {
                $terrain->referenceCadastrale->delete();
            }

            // ✅ Suppression du dossier (relation directe)
            if ($terrain->dossier) {
                $terrain->dossier->delete();
            }

            // ✅ Suppression du terrain
            $terrain->delete();

            // ✅ Suppression du titulaire s'il n'a plus de terrains
            if ($titulaire && $titulaire->terrains()->count() === 0) {
                $titulaire->delete();
            }

            DB::commit();

            return redirect()->route('donnee.create')->with([
                'success' => true,
                'message' => 'Terrain supprimé avec succès.'
            ]);
  
        } catch (\Throwable $e) {
            DB::rollBack();

            
            return redirect()->route('donnee.create')->with([
                'success' => false,
                'message' => 'Erreur serveur lors de la suppression.',
                'error' => $e->getMessage() // utile en dev
            ], 500);
 

        }
    }

    public function searchTerrain(Request $request)
    {
        $parcelle = trim($request->parcelle);
        $section = trim($request->section);

        $terrain = Terrain::whereRaw("RIGHT(txt_nicad, 5) = ?", [$parcelle])
            ->whereRaw('TRIM(txt_num_section) = ?', [$section])
            ->first();

        return response()->json([
            'txt_num_dossier' => $terrain?->dossier?->txt_num_dossier,
            'txt_nicad' => $terrain?->txt_nicad,
            'nbr_surface' => $terrain?->nbr_surface,
            'txt_num_lotissement' => $terrain?->txt_num_lotissement,
            'txt_lotissement' => $terrain?->txt_lotissement,
        ]);
    }
}