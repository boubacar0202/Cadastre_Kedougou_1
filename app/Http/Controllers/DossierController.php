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

        // Récupérer le dernier dossier de l'année
        $last = Dossier::where('txt_num_dossier', 'like', '%/'.$annee)
            ->orderByRaw('CAST(SUBSTRING_INDEX(txt_num_dossier, "/", 1) AS UNSIGNED) DESC')
            ->first();

        $lastOrdre = Dossier::max('txt_num_dordre');

        // Si $lastOrdre est un entier simple
        $nextOrdre = $lastOrdre ? $lastOrdre + 1 : 1;
 
        if ($last) {
            [$prefix, $year] = explode('/', $last->txt_num_dossier);
            $lastNumber = (int) $prefix;
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            // aucun dossier encore créé pour l'année courante
            $nextNumber = '00001';
        }

        $numDossier = $nextNumber . '/' . $annee;

        return response()->json([
            'num_dossier' => $numDossier,
            'num_dordre'  => $nextOrdre,
        ]);
    }

  
}
 
 