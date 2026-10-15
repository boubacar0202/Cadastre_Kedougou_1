<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Arrivee;
use App\Models\Depart; 
use App\Models\Trimestre;
use Carbon\Carbon; 
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Str;

class TrimestreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trimestres = Trimestre::all();
        $morcellementsCount = Arrivee::where(DB::raw('LOWER(txt_designation)'), 'like', '%Morcellements%')->count();
   
        return Inertia::render('parametre/trimestre/create', [
            'trimestres' => $trimestres,
            'morcellementsCount' => $morcellementsCount
        ]);
    }
     
    // fonction pour afficher les données d'un trimestre spécifique
    public function create(Request $request)
    {
        // 🔹 1. Trimestre sélectionné
        $trimestre = $request->input('trimestre');

        if ($trimestre && str_contains($trimestre, '-')) {
            [$year, $quarter] = explode('-', $trimestre);
        } else {
            $now = Carbon::now();
            $quarter = $now->quarter;
            $year = $now->year;
        }

        $year = (int) $year;
        $quarter = (int) $quarter;

        // 🔹 2. Début et fin du trimestre
        $startOfQuarter = Carbon::createFromDate($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
        $endOfQuarter = (clone $startOfQuarter)->addMonths(3)->subDay()->endOfDay();

        // // 🔹 3. Navigation précédent / suivant
        $current = Carbon::createFromDate($year, ($quarter - 1) * 3 + 1, 1);
        $prevQuarter = (clone $current)->subMonths(3);
        $nextQuarter = (clone $current)->addMonths(3);

        $prev = $prevQuarter->year . '-' . $prevQuarter->quarter;
        $next = $nextQuarter->year . '-' . $nextQuarter->quarter;

        // Bloquer le prochain si c'est le trimestre actuel
        $now = Carbon::now();
        if ($year == $now->year && $quarter == $now->quarter) { 
            $next = null;
        }
        $now = Carbon::now();
  
        // 🔹 4. Trimestres disponibles
        $trimestres = Trimestre::orderBy('id')->get();

        // 🔹 5. Courriers retournés
        $courriersRetourners = Depart::where('txt_caracterecd', 'Dossier Retourne')
            ->whereBetween('dt_datecouriercd', [$startOfQuarter, $endOfQuarter])
            ->count();

        // 🔹 6. Désignations
        $designations = [
            "Morcellements",
            "Réquisition d'immatriculation",
            "Demande Avis Technique",
            "Demande de terrain/Echange",
            "Prospection de terrain",
            "Autorisation de construction",
            "Autorisation de lotir",
            "Demande d'états des lieux",
            "Demande de délimitation",
            "Demande de reconstruction",
            "Réquisition DSCOS",
            "Tribunal",
            "Litiges",
            "Demande d'extraits de plan",
            "Demande de situation foncière",
            "Demande de Cession définitive a Titre Gratuit",
            "Demande de Régularisation par voie de Bail",
            "Demande d'attestation du Cadastre",
            "Projets de Lotissements reçus",
            "Réceptions de lotissements",
            "Lotissements réalisés sans respect des procédures",
            "Duplication de CIC",
            "Demande de Titre foncier",
            "Autirisationde morceler",
            "Demande d'évaluation",
            "Nombre de fiches de mise à jour reçues",
            "Nombre de dossiers techniques en attente de fiches de mise à jour",
        ];

        // 🔹 7. Initialisation des tableaux
        $stockFin = [];
        $countsByTrimestre = [];
        $countsTraiterByTrimestre = [];
        $instancesRestantes = [];

        foreach ($designations as $designation) {

            // 7a. Stock fin précédent
            $stockFin[$designation] = Arrivee::leftJoin(
                    'departs',
                    'departs.txt_referencecourierarriveecd',
                    '=',
                    'arrivees.txt_reference', 
                )
                ->where('arrivees.txt_designation', 'like', "%{$designation}%")
                ->where('arrivees.dt_datearrivee', '<', $startOfQuarter) 
                ->where('arrivees.txt_typecourrier', '!=', 'BE à réponses Multiples')
                ->whereNull('departs.txt_referencecourierarriveecd')
                ->count();

            // 7b. Reçus du trimestre
            $countsByTrimestre[$designation] = Arrivee::where('txt_designation', 'like', "%{$designation}%")
                ->where('txt_typecourrier', '!=', 'BE à réponses Multiples')
                ->whereBetween('dt_datearrivee', [$startOfQuarter, $endOfQuarter])
                ->count();

            // 7c. Traités du trimestre
            $countsTraiterByTrimestre[$designation] = Arrivee::where('txt_designation', 'like', "%{$designation}%")
                ->whereBetween('dt_datearrivee', [$startOfQuarter, $endOfQuarter])
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('departs') 
                        ->whereColumn('departs.txt_referencecourierarriveecd', 'arrivees.txt_reference');
                })
                ->count();

            // 7d. Instances restantes
            $instancesRestantes[$designation] = max(
                ($stockFin[$designation] + $countsByTrimestre[$designation]) - $countsTraiterByTrimestre[$designation],
                0
            );
        }

        // 🔹 8. Retour Inertia
        return Inertia::render('parametre/trimestre/create', [
            'trimestres' => $trimestres,
            'countsByTrimestre' => $countsByTrimestre,
            'countsTraiterByTrimestre' => $countsTraiterByTrimestre,
            'stockFin' => $stockFin,
            'courriersRetourners' => $courriersRetourners,
            'instancesRestantes' => $instancesRestantes,

            'trimestre' => $year . '-' . $quarter,
            'prev' => $prev,
            'next' => $next,
            'trimestreLabel' => "T{$quarter} {$year}",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}