<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use GuzzleHttp\Psr7\Request;

class DossierController extends Controller
{
    //
    // public function getNextDossier()
    // {
    //     $lastDossier = Dossier::whereYear('created_at', date('Y'))
    //         ->orderBy('id', 'desc')
    //         ->first();
    
    //     $number = $lastDossier ? (int)substr($lastDossier->txt_num_dossier, 0, 5) + 1 : 1;
    
    //     $nextNumDossier = sprintf('%05d/%s', $number, date('Y'));
    
    //     return response()->json([
    //         'num_dossier' => $nextNumDossier
    //     ]);
    // }

    public function getNextDossier()
    {
        $annee = date('Y');

        $last = Dossier::whereYear('created_at', $annee)
            ->orderByDesc('txt_num_dordre')
            ->first();

        $nextOrdre = $last ? (int)$last->txt_num_dordre + 1 : 1;

        $numDossier = sprintf('%05d/%s', $nextOrdre, $annee);

        return response()->json([
            'num_dossier' => $numDossier,
            'num_dordre' => $nextOrdre,
        ]);
    }


}
