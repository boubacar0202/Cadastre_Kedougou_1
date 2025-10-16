<?php

namespace App\Http\Controllers;

use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CodeAcces;

class TerrainController extends Controller
{
    //
    public function destroy(Terrain $terrain)
    {
        // 
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

        // return redirect()->back()->with('success', 'Terrain supprimé avec succès.');
        
        return redirect()->back()->with('success', 'Terrain supprimé...');
    }

 

}
 