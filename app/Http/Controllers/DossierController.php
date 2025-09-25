<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use GuzzleHttp\Psr7\Request; 
use App\Models\CodeAcces;

 

class DossierController extends Controller
{  

    public function getNextDossier()
    {
        $annee = date('Y');

        // Récupérer le dernier dossier de l'année en cours uniquement
        $last = Dossier::where('txt_num_dossier', 'like', '%/'.$annee)
            ->orderByDesc('id')
            ->first();

        $lastOrdre = Dossier::whereYear('created_at', $annee)
            ->orderByDesc('txt_num_dordre')
            ->first();

        $nextOrdre = $lastOrdre ? (int)$lastOrdre->txt_num_dordre + 1 : 1;

        if ($last) {
            // Exemple : "00001/2025" → ["00001", "2025"]
            [$lastNumber, $lastYear] = explode('/', $last->txt_num_dossier);

            // Incrémenter seulement si l'année est la même
            $nextNumber = str_pad(((int)$lastNumber + 1), 5, '0', STR_PAD_LEFT);
        } else {
            // Aucun dossier pour cette année → on démarre à 00001
            $nextNumber = '00001';
        }

        $numDossier = $nextNumber . '/' . $annee;

        return response()->json([
            'num_dossier' => $numDossier,
            'num_dordre' => $nextOrdre,
        ]);
    }
 
 
}
 
 