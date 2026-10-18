<?php

namespace App\Http\Controllers;

use App\Http\Requests\Parametre\UpdatePointDensifierRequest;
use App\Models\ActiviteUtilisateur;
use App\Models\Arrondissement;
use App\Models\CodeAcces;
use App\Models\Commune;
use App\Models\Departement;
use App\Models\Lotissement; 
use App\Models\PointDensifier;
use App\Models\Mutation;
use Illuminate\Http\Request;
use App\Models\Parametre; 
use App\Models\EvaluationTerrain;
use App\Models\User;
use App\Models\Message;
use App\Models\Shapefile;
use App\Models\Dossier;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class ParametreController extends Controller
{
    /**
     * index
     */
    public function index()
    {
        //
        $lotissements = Lotissement::with(['departement', 'arrondissement', 'commune'])->get();  
        return Inertia::render('parametre/lotissement/index', [
            'lotissements' => $lotissements, 
        ]);
    }

    /**
     * create
     */
    public function create()
    {
        //
        $departements = Departement::all();  
        return Inertia::render("parametre/lotissement/create", [  
            "departements" => $departements, 
            "arrondissements" => [],
        ]);

    }

    /** 
     * edit
     */ 
    public function edit($id)
    {
        $lotissement = Lotissement::with(['departement', 'arrondissement', 'commune'])->findOrFail($id);

        $departements = Departement::all();
        $arrondissements = Arrondissement::where('departement_id', $lotissement->slt_departement)->get();
        $communes = Commune::where('arrondissement_id', $lotissement->slt_arrondissement)->get();

        return Inertia::render("parametre/lotissement/edit", [
            "lotissements" => $lotissement,
            "departements" => $departements,
            "arrondissements" => $arrondissements,
            "communes" => $communes,
        ]);
    }
 
    /**
     * admin
     */
    public function admin()
    { 
        $users = User::where('role', 'admin')->where('is_validated', true)->orderBy('name')->get();
        $pendingUsers = User::where('is_validated', false)->latest()->get();

        return Inertia::render("parametre/admin", [
            'users' => $users,
            'pendingUsers' => $pendingUsers,
        ]); 
    }

    /**
     * Mutation
     */
    public function mutation()
    {
        $mutation = Mutation::all();  
        return Inertia::render("parametre/mutation", [ 
            "mutations" => $mutation
        ]); 
    }

    /*
    * Mutation Edit
     */
    public function editMutation($id)
    {
        $mutation = Mutation::findOrFail($id);

        return Inertia::render("parametre/mutationEdit", [ 
            "mutation" => $mutation
        ]); 
    }

    /*
    * Mutation Update
     */
    public function destroyMutation(Request $request, $id)
    { 
        $request->validate([
            'code' => 'required|string'
        ]);

        // Vérifier le code
        $code = CodeAcces::where('id', 3)
                        ->where('code', $request->code)
                        ->first();

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Code d’accès invalide. Contacter l’administrateur !'
            ], 403);
        }

        // Récupérer l'utilisateur  
        $mutation = Mutation::findOrFail($id);
 

        // Supprimer l'utilisateur
        $mutation->delete();

        return response()->json([
            'success' => true,
            'message' => "{$mutation->txt_numeroMutation}; supprimé avec succès."
        ]);
    }

    /**
     * Departements
     */
    public function getDepartements()
    {
        $departements = Departement::all();
        return response()->json([
            'departements' => $departements
        ]);
    }

    /**
     * Arrondissements par département
     * */
    public function getArrondissementByDepartement(int $id)
    {
        $arrondissements = Arrondissement::where('departement_id', $id)->get();
        return response()->json([
            'arrondissements' => $arrondissements
        ]);
    }

    /**
     * Communes par arrondissement
     */
    public function getCommunesByArrondissement(int $id)
    {
        $communes = Commune::where('arrondissement_id', $id)->get();
        return response()->json([
            'communes' => $communes
        ]);
    }
  
    /**
     * User
     */
    public function user()
    {
        $users = User::where('role', 'user')->where('is_validated', true)->orderBy('name')->get();
        return Inertia::render("parametre/user", [
            'users' => $users,
        ]);
    }
  
    /**
     * Store  
     */
    public function store(Request $request)
    {
        //
        $validatedData = $request->validate([
            'txt_denomination' => 'string|required',
            'txt_nombreParcelle' => 'string|required', 
            'slt_departement' => 'string|required',
            'slt_arrondissement' => 'string|required',
            'slt_commune' => 'string|required',
            'txt_surfaceLotissement' => 'string|required',
            'txt_documentsAfferants' => 'file|nullable|mimes:pdf'
        ],
            [
                'txt_denomination.required' => 'Le champ "Denomination" est obligatoire.',
                'txt_nombreParcelle.required' => 'Le champ "Nombre de parcelles" est obligatoire.',
                'slt_departement.required' => 'Le champ "Departement" est obligatoire.',
                'slt_arrondissement.required' => 'Le champ "Arrondissement" est obligatoire.',
                'slt_commune.required' => 'Le champ "Commune" est obligatoire.',
                'txt_surfaceLotissement.required' => 'Le champ "Surface" est obligatoire.'
            ]);

        // traitement fichier PDF
        if ($request->hasFile('txt_documentsAfferants')) {
            $validatedData['txt_documentsAfferants'] = $request->file('txt_documentsAfferants')->store('lotissement', 'public');
        }

        
        Lotissement::create([
            'txt_denomination' => $validatedData['txt_denomination'],
            'txt_nombreParcelle' => $validatedData['txt_nombreParcelle'],
            'txt_denomination' => $validatedData['txt_denomination'],
            'slt_departement' => $validatedData['slt_departement'],
            'slt_arrondissement' => $validatedData['slt_arrondissement'],
            'slt_commune' => $validatedData['slt_commune'],
            'txt_surfaceLotissement' => $validatedData['txt_surfaceLotissement'],
            'txt_documentsAfferants' => $validatedData['txt_documentsAfferants']
        ]);
        
         
        // return redirect()->back()->with('success', '✅ Données Lotissement enregistrée !');
        return redirect()->route('parametre.lotissement.create')->with('success', '✅ Données Lotissement enregistrée !');
           
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

 
    /**
     * Update 
     */ 
    public function update(Request $request, string $id)
    {
        $lotissement = Lotissement::findOrFail($id);

        $validateData = $request->validate([
            'txt_denomination' => 'string|required',
            'txt_nombreParcelle' => 'string|required',
            'slt_departement' => 'required|integer|exists:departements,id',
            'slt_arrondissement' => 'required|integer|exists:arrondissements,id',
            'slt_commune' => 'required|integer|exists:communes,id',
            'txt_surfaceLotissement' => 'string|required',
            'txt_documentsAfferants' => 'file|nullable|mimes:pdf'
        ]);

        // --- Gestion du fichier ---
        if ($request->hasFile('txt_documentsAfferants')) {
 
            if ($lotissement->txt_documentsAfferants && Storage::disk('public')->exists($lotissement->txt_documentsAfferants)) {
                Storage::disk('public')->delete($lotissement->txt_documentsAfferants);
            }
 
            $validateData['txt_documentsAfferants'] = 
                $request->file('txt_documentsAfferants')->store('lotissement', 'public');

        } else { 
            $validateData['txt_documentsAfferants'] = $lotissement->txt_documentsAfferants;
        }
 
        $lotissement->update($validateData);

        return redirect()->route('parametre.lotissement.index')
            ->with('success', 'Lotissement '.$lotissement->txt_denomination.' modifié avec succès');
    } 

    /**
     * Remove the specified resource from storage.
     */
 
    public function destroy(Request $request, $id)
    {
        // Valider le code fourni
        $request->validate([
            'code' => 'required|string'
        ]);

        // Chercher un code spécifique (par exemple id = 3)
        $code = CodeAcces::where('id', 3)
                        ->where('code', $request->code)
                        ->first();

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Code d’accès estinvalide. Contacter l’administrateur !'
            ], 403);
        }

        // Trouver le lotissement
        $lotissement = Lotissement::findOrFail($id);

        // Supprimer PDF
        if ($lotissement->txt_documentsAfferants && Storage::disk('public')->exists($lotissement->txt_documentsAfferants)) {
            Storage::disk('public')->delete($lotissement->txt_documentsAfferants);
        }

        $nom = $lotissement->txt_denomination;

        // Supprimer le lotissement
        $lotissement->delete();

        return response()->json([
            'success' => true,
            'message' => "Lotissement \"$nom\" supprimé avec succès."
        ]);
    }

    private function compteProtege(User $user): bool
    {
        return (int) $user->id === 4
            && strcasecmp(trim((string) $user->email), 'mbengueboubacar095@gmail.com') === 0;
    }

    public function adminEdit($id)
    {
        return $this->editUser($id);
    }


    // Modifier un utilisateur
    public function editUser($id)
    {
        $user = User::findOrFail($id);

        if ($this->compteProtege($user)) {
            $message = 'Impossible de modifier le compte de ' . $user->name . ' car il est protégé.';

            return redirect()->route('parametre.admin')->with('error', $message);
        }

        return Inertia::render('parametre/adminEdit', [
            'user' => $user
        ]);
    } 

    // Modifier un utilisateur
    public function updateUser(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->merge([
            'role' => User::normalizeRole($request->input('role')),
        ]);
 
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:admin,user',
        ], [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email n'est pas valide.",
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle choisi est invalide.',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        if ($this->compteProtege($user)) {
            $message = 'La modification de l\'utilisateur ' . $user->name . ' n\'est pas autorisée.';

            return back()
                ->withErrors(['role' => $message])
                ->with('error', $message);
        }

        $user->update($data);

        return redirect()
            ->route($user->role === 'user' ? 'parametre.user' : 'parametre.admin')
            ->with('success', 'Utilisateur ' . $user->name . ' modifié avec succès');
    }

    public function storeUser(Request $request)
    {
        $authUser = $request->user();

        if (! $authUser || $authUser->role !== 'admin') {
            return back()->with('error', 'Seuls les administrateurs peuvent créer un compte.');
        }

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'role' => User::normalizeRole($request->input('role')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:admin,user',
        ], [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email n'est pas valide.",
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle choisi est invalide.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_validated' => true,
        ]);

        $liste = $user->role === 'user' ? 'Utilisateurs' : 'administrateurs';

        return redirect()
            ->route('parametre.admin')
            ->with('success', 'Le compte de ' . $user->name . ' a été créé. Il figure dans la liste des ' . $liste . '.');
    }

    public function validateUser(Request $request, $id)
    {
        $authUser = $request->user();

        if (! $authUser || $authUser->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les administrateurs peuvent valider un compte.',
            ], 403);
        }

        $user = User::findOrFail($id);

        if ($this->compteProtege($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier le compte de ' . $user->name . ' car il est protégé.',
            ], 403);
        }

        if ($user->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà validé.',
            ], 422);
        }

        $user->update(['is_validated' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Le compte de ' . $user->name . ' a été validé. Il peut maintenant se connecter.',
        ]);
    } 

    // Suppression un utilisateur
    public function destroyUser(Request $request, $id)
    { 
        $request->validate([
            'code' => 'required|string'
        ]);

        // Vérifier le code
        $code = CodeAcces::where('id', 3)
                        ->where('code', $request->code)
                        ->first();

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Code d’accès invalide. Contacter l’administrateur !'
            ], 403);
        }

        $authUser = $request->user();

        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible : seuls les administrateurs peuvent supprimer un utilisateur.'
            ], 403);
        }

        // Récupérer l'utilisateur  
        $user = User::findOrFail($id);

        if ($this->compteProtege($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer le compte de ' . $user->name . ' car il est protégé.',
            ], 403);
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            Log::error('Suppression utilisateur impossible', [
                'id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer {$user->name} : des données y sont encore liées.",
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => "{$user->name} supprimé avec succès."
        ]);
    }

   /**
     * Chart - Page des graphiques et statistiques
     */
    public function chart()
    { 
        // Données pour le Pie Chart - Répartition des dossiers par statut
        $pieData = $this->getPieChartData();
        
        // Données pour le Bar Chart - Évolution mensuelle
        $barData = $this->getBarChartData();
        
        // Évaluation des utilisateurs pour le trimestre en cours
        $evaluation = $this->getEvaluationUtilisateurs();

        // Données supplémentaires pour le dashboard
        $stats = $this->getStats();

        return Inertia::render("parametre/chart", [ 
            'pieData' => $pieData,
            'barData' => $barData,
            'evaluation' => $evaluation,
            'stats' => $stats
        ]); 
    }

    /**
     * API - Récupérer les données des graphiques en temps réel
     */
    public function getChartData(Request $request)
    {
        return response()->json([
            'pie' => $this->getPieChartData(),
            'bar' => $this->getBarChartData(),
            'evaluation' => $this->getEvaluationUtilisateurs(
                $request->integer('annee') ?: null,
                $request->integer('trimestre') ?: null,
            ),
        ]);
    }

    /**
     * API - Récupérer les statistiques globales
     */
    public function getStatsData()
    {
        return response()->json($this->getStats());
    }
 
    /**
     * Données pour le Pie Chart
     */
    // Objectif annuel (valeur estimée)
    const objectif_annuel = 1500;

    private function getPieChartData()
    {
 
        try {
            // Compter les dossiers par service rendu
            $data = Dossier::select('slt_service_rendu', DB::raw('count(*) as total'))
                ->groupBy('slt_service_rendu')
                ->get();
            
            // Valeur venal 
            $totalValeurVenale = EvaluationTerrain::sum('nbr_valeurVenaleLimeuble'); 

            
            // Calcul du pourcentage (capé à 100% si vous voulez un max)
            $pourcentage = ($totalValeurVenale / self::objectif_annuel) * 100;
            $pourcentage = min(100, round($pourcentage, 2)); // optionnel : plafonner à 100%

            $labels = [];
            $values = [];
            $colors = [];

            if ($data->isEmpty()) {
                // Données par défaut
                return [
                    'labels' => ['En attente', 'Delimitation', 'Extrait de plan', 'Implantation', 'Enquete castastrale', 
                    'Etat des lieux', 'Expertise immobilière', 'Division', 'Reconstitution des limites', 'Plan de situation'],
                    'data' => [25, 20, 15, 10],
                    'colors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
                ];
            }

            foreach ($data as $item) {
                // Si le service rendu est null, on le remplace par "En attente"
                $label = $item->slt_service_rendu ?? 'En attente';
                $labels[] = $label;
                $values[] = (int) $item->total;
                $colors[] = $this->getRandomColor();
            }

            return [
                'labels' => $labels,
                'data' => $values,
                'colors' => $colors,
                'totalValeurVenale' => $totalValeurVenale,
                'pourcentage' => $pourcentage
            ];
        } catch (\Exception $e) {
            return [
                'labels' => ['En attente', 'Delimitation', 'Extrait de plan', 'Implantation', 'Enquete castastrale', 
                    'Etat des lieux', 'Expertise immobilière', 'Division', 'Reconstitution des limites', 'Plan de situation' ],
                'data' => [25, 20, 15, 10],
                'colors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'], 
                'totalValeurVenale' => $totalValeurVenale,
                'pourcentage' => $pourcentage
            ];
        }
    }

 
    /**
     * Données pour le Bar Chart (avec deux séries superposées)
     * - Extrait de plan
     * - Délimitation
     */
    private function getBarChartData()
    {
        try {
            // Statistiques des 6 derniers mois
            $months = collect(range(0, 5))->map(function ($i) {
                return now()->subMonths($i)->format('M');
            })->reverse()->values();

            $currentMonth = now()->subMonths(5)->startOfMonth();
            $extraitPlanData = [];
            $delimitationData = [];

            for ($i = 0; $i < 6; $i++) {
                $startDate = $currentMonth->copy()->startOfMonth();
                $endDate = $currentMonth->copy()->endOfMonth();

                // Compter les "Extrait de plan" pour ce mois
                $extraitPlanCount = Dossier::where('slt_service_rendu', 'Extrait de plan')
                    ->whereBetween('dt_date_creation', [$startDate, $endDate])
                    ->count();

                // Compter les "Délimitation" pour ce mois
                $delimitationCount = Dossier::where('slt_service_rendu', 'Delimitation')
                    ->whereBetween('dt_date_creation', [$startDate, $endDate])
                    ->count();

                $extraitPlanData[] = $extraitPlanCount;
                $delimitationData[] = $delimitationCount;

                $currentMonth->addMonth();
            }

            return [
                'labels' => $months->toArray(),
                'datasets' => [
                    [
                        'label' => 'Extrait de plan',
                        'data' => $extraitPlanData,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.7)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 2,
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => 'Délimitation',
                        'data' => $delimitationData,
                        'backgroundColor' => 'rgba(255, 99, 132, 0.7)',
                        'borderColor' => 'rgba(255, 99, 132, 1)',
                        'borderWidth' => 2,
                        'borderRadius' => 4,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Erreur dans getBarChartData: ' . $e->getMessage());
            return [
                'labels' => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin'],
                'datasets' => [
                    [
                        'label' => 'Extrait de plan',
                        'data' => [8, 12, 5, 7, 3, 6],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.7)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 2,
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => 'Délimitation',
                        'data' => [4, 7, 3, 5, 2, 4],
                        'backgroundColor' => 'rgba(255, 99, 132, 0.7)',
                        'borderColor' => 'rgba(255, 99, 132, 1)',
                        'borderWidth' => 2,
                        'borderRadius' => 4,
                    ]
                ]
            ];
        }
    }

    /**
     * Données pour le Grid
     */
    private function getGridData()
    {
        try {
            // Statistiques détaillées mensuelles
            $monthlyData = Dossier::select(
                DB::raw('DATE_FORMAT(dt_date_creation, "%Y-%m") as mois'),
                DB::raw('count(*) as total')
            )
                ->groupBy('mois')
                ->orderBy('mois', 'desc')
                ->limit(6)
                ->get();

            if ($monthlyData->isEmpty()) {
                return $this->getDefaultGridData();
            }

            return $monthlyData->map(function ($item) {
                try {
                    $extraitsDePlan = Dossier::where('slt_service_rendu', 'Extrait de plan')
                        ->where(DB::raw('DATE_FORMAT(dt_date_creation, "%Y-%m")'), $item->mois)
                        ->count();
                    
                    $delimitation = Dossier::where('slt_service_rendu', 'Delimitation')
                        ->where(DB::raw('DATE_FORMAT(dt_date_creation, "%Y-%m")'), $item->mois)
                        ->count();

                    $rejetes = Dossier::where('slt_service_rendu', 'rejete')
                        ->where(DB::raw('DATE_FORMAT(dt_date_creation, "%Y-%m")'), $item->mois)
                        ->count();

                    return [
                        'mois' => $item->mois,
                        'dossiers' => (int) $item->total,
                        'extraits_de_plan' => $extraitsDePlan,
                        'delimitation' => $delimitation,
                        'rejetes' => $rejetes
                    ];
                } catch (\Exception $e) {
                    return [
                        'mois' => $item->mois,
                        'dossiers' => (int) $item->total,
                        'extraits_de_plan' => 0,
                        'delimitation' => 0,
                        'rejetes' => 0
                    ];
                }
            })->toArray();
        } catch (\Exception $e) {
            return $this->getDefaultGridData();
        }
    }

    /**
     * Données par défaut pour le Grid
     */
    private function getDefaultGridData()
    {
        return [
            ['mois' => 'Janvier', 'dossiers' => 12, 'extraits_de_plan' => 5, 'delimitation' => 7, 'rejetes' => 0],
            ['mois' => 'Février', 'dossiers' => 19, 'extraits_de_plan' => 8, 'delimitation' => 11, 'rejetes' => 0],
            ['mois' => 'Mars', 'dossiers' => 3, 'extraits_de_plan' => 2, 'delimitation' => 1, 'rejetes' => 0],
            ['mois' => 'Avril', 'dossiers' => 5, 'extraits_de_plan' => 3, 'delimitation' => 2, 'rejetes' => 0],
            ['mois' => 'Mai', 'dossiers' => 2, 'extraits_de_plan' => 1, 'delimitation' => 1, 'rejetes' => 0],
            ['mois' => 'Juin', 'dossiers' => 3, 'extraits_de_plan' => 2, 'delimitation' => 1, 'rejetes' => 0]
        ];
    }

    /**
     * Statistiques globales
     */
    private function getStats()
    {
        try {
            // Vérifier si la table Dossier existe
            if (class_exists(Dossier::class) && Schema::hasTable('dossiers')) {
                // ✅ Total des dossiers
                $totalDossiers = Dossier::count();
                
                // ✅ Compter par service rendu (différents types)
                $dossiersEnCours = Dossier::whereNull('slt_service_rendu')->count(); // Pas encore de service rendu
                $dossiersEnAttente = Dossier::whereNull('slt_service_rendu')->count(); // En attente de traitement
                $dossiersDelimitation = Dossier::where('slt_service_rendu', 'Delimitation')->count();
                $dossiersExtraitPlan = Dossier::where('slt_service_rendu', 'Extrait de plan')->count();
                $dossiersImplantation = Dossier::where('slt_service_rendu', 'Implantation')->count();
                $dossiersPlanSituation = Dossier::where('slt_service_rendu', 'Plan de situation')->count();
                $dossiersDivision = Dossier::where('slt_service_rendu', 'Division')->count();
                $dossiersReconstitution = Dossier::where('slt_service_rendu', 'Reconstitution des limites')->count();
                $dossiersEtatLieux = Dossier::where('slt_service_rendu', 'Etat des lieux')->count();
                $dossiersExpertise = Dossier::where('slt_service_rendu', 'Expertise immobilière')->count();
                $dossiersEnquete = Dossier::where('slt_service_rendu', 'Enquête cadastrale')->count();
                $dossiersTerrainEnqueter = EvaluationTerrain::whereNotNull('nbr_valeurVenaleLimeuble')->count(); // Dossiers ayant un terrain enqueter
                
                // ✅ Dossiers terminés (ceux qui ont un service rendu)
                $dossiersTermines = Dossier::whereNotNull('slt_service_rendu')->count();
                
                // ✅ Dossiers rejetés (si vous avez une colonne pour ça, sinon on le met à 0)
                $dossiersRejetes = 0;

                            
                // Calcul du pourcentage (capé à 100% si vous voulez un max)
                $pourcentage = ($dossiersTerrainEnqueter / self::objectif_annuel) * 100;
                $pourcentage = min(100, round($pourcentage, 2)); // optionnel : plafonner à 100%

                $dossiersPourcentageTerrainEnqueter = $pourcentage;
                
            } else {
                $totalDossiers = 0;
                $dossiersEnCours = 0;
                $dossiersEnAttente = 0;
                $dossiersDelimitation = 0;
                $dossiersExtraitPlan = 0;
                $dossiersImplantation = 0;
                $dossiersPlanSituation = 0;
                $dossiersDivision = 0;
                $dossiersTerrainEnqueter = 0;
                $dossiersReconstitution = 0;
                $dossiersEtatLieux = 0;
                $dossiersExpertise = 0;
                $dossiersEnquete = 0;
                $dossiersTermines = 0;
                $dossiersRejetes = 0;
                $dossiersPourcentageTerrainEnqueter = 0;
            }

            // Autres tables 
            $totalMutations = class_exists(Mutation::class) && Schema::hasTable('mutations') ? Mutation::count() : 0;
            $totalLotissements = class_exists(Lotissement::class) && Schema::hasTable('lotissements') ? Lotissement::count() : 0;
            $totalUtilisateurs = class_exists(User::class) && Schema::hasTable('users') ? User::count() : 0;

            return [
                'total_dossiers' => $totalDossiers,
                'dossiers_en_cours' => $dossiersEnCours,
                'dossiers_en_attente' => $dossiersEnAttente,
                'dossiers_delimitation' => $dossiersDelimitation,
                'dossiers_extrait_plan' => $dossiersExtraitPlan,
                'dossiers_implantation' => $dossiersImplantation,
                'dossiers_plan_situation' => $dossiersPlanSituation,
                'dossiers_division' => $dossiersDivision,
                'dossiers_reconstitution' => $dossiersReconstitution,
                'dossiers_etat_lieux' => $dossiersEtatLieux,
                'dossiers_expertise' => $dossiersExpertise,
                'dossiers_enquete' => $dossiersEnquete,
                'dossiers_termines' => $dossiersTermines,
                'dossiers_rejetes' => $dossiersRejetes, 
                'total_mutations' => $totalMutations,
                'total_lotissements' => $totalLotissements,
                'total_utilisateurs' => $totalUtilisateurs,
                'dossiers_terrain_enqueter' => $dossiersTerrainEnqueter,
                'dossiers_pourcentage_terrain_enqueter' => $dossiersPourcentageTerrainEnqueter
            ];
        } catch (\Exception $e) {
            Log::error('Erreur dans getStats: ' . $e->getMessage());
            return [
                'total_dossiers' => 0,
                'dossiers_en_cours' => 0,
                'dossiers_en_attente' => 0,
                'dossiers_delimitation' => 0,
                'dossiers_extrait_plan' => 0,
                'dossiers_implantation' => 0,
                'dossiers_plan_situation' => 0,
                'dossiers_division' => 0,
                'dossiers_reconstitution' => 0,
                'dossiers_etat_lieux' => 0,
                'dossiers_expertise' => 0,
                'dossiers_enquete' => 0,
                'dossiers_termines' => 0,
                'dossiers_rejetes' => 0,
                'total_mutations' => 0,
                'total_lotissements' => 0,
                'total_utilisateurs' => 0,
                'dossiers_terrain_enqueter' => 0
            ];
        }
    }

    /**
     * Générer une couleur aléatoire
     */
    private function getRandomColor()
    {
        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4',
            '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F'
        ];
        return $colors[array_rand($colors)];
    }


    /**
     * API - Récupérer les données des états de cession par trimestre
     * Uniquement pour les dossiers avec slt_service_rendu = 'Délimitation'
     * Avec répartition par type d'état de cession
     */ 

    public function getCessionData(Request $request)
    {
        // Récupérer les paramètres
        $sltServiceRendu = $request->input('slt_service_rendu', 'Délimitation');
        $offset = (int) $request->input('offset', 0);

        // Empêcher d'aller dans le futur (optionnel)
        if ($offset > 0) {
            $offset = 0;
        }

        // Obtenir les mois du trimestre
        $months = $this->getQuarterMonths($offset);

        // Récupérer les données pour chaque mois
        $monthData = [];
        foreach ($months as $m) {
            $monthData[] = $this->getMonthCessionData($m['month'], $m['year'], $sltServiceRendu);
        }

        // Regrouper tous les types d'états de cession sur les 3 mois
        $allTypes = [];
        foreach ($monthData as $data) {
            if (isset($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $type => $_) {
                    if ($type !== 'TOTAL') {
                        $allTypes[$type] = true;
                    }
                }
            }
        }

        // Total général
        $totalGeneral = [
            'montant_etat'       => array_sum(array_column($monthData, 'montant_etat')),
            'nombre_unitaire'    => array_sum(array_column($monthData, 'nombre_unitaire')),
            'montant_unitaire'   => 0,
            'montant_total'      => array_sum(array_column($monthData, 'montant_total')),
            'details'            => [],
        ];

        if ($totalGeneral['nombre_unitaire'] > 0) {
            $totalGeneral['montant_unitaire'] = round($totalGeneral['montant_etat'] / $totalGeneral['nombre_unitaire']);
        }

        // Détails par type (cumul sur les 3 mois)
        foreach ($allTypes as $type => $_) {
            $totalGeneral['details'][$type] = [
                'nombre' => 0,
                'montant_total' => 0,
                'montant_unitaire' => 0
            ];
            foreach ($monthData as $data) {
                if (isset($data['details'][$type])) {
                    $totalGeneral['details'][$type]['nombre'] += $data['details'][$type]['nombre'];
                    $totalGeneral['details'][$type]['montant_total'] += $data['details'][$type]['montant_total'];
                }
            }
            if ($totalGeneral['details'][$type]['nombre'] > 0) {
                $totalGeneral['details'][$type]['montant_unitaire'] =
                    round($totalGeneral['details'][$type]['montant_total'] / $totalGeneral['details'][$type]['nombre']);
            }
        }

        // Construire la réponse
        $response = [
            'months' => [],
            'total'  => $totalGeneral,
            'types'  => array_keys($allTypes),
        ];

        foreach ($months as $i => $m) {
            $response['months'][] = [
                'name'             => $m['name'],
                'montant_etat'     => $monthData[$i]['montant_etat'],
                'nombre_unitaire'  => $monthData[$i]['nombre_unitaire'],
                'montant_unitaire' => $monthData[$i]['montant_unitaire'],
                'montant_total'    => $monthData[$i]['montant_total'],
                'details'          => $monthData[$i]['details'],
            ];
        }

        return response()->json($response);
    }

    /**
     * Récupérer les données de cession pour un mois donné
     * Uniquement pour les dossiers avec slt_service_rendu = 'Délimitation'
     * Avec répartition par type d'état de cession
     */ 
        /**
     * Retourne les trois mois d'un trimestre à partir d'un offset.
     */
    private function getQuarterMonths(int $offset = 0): array
    {
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        // Mois de début du trimestre en cours (1, 4, 7, 10)
        $startMonth = $currentMonth - (($currentMonth - 1) % 3);

        // Appliquer l'offset (en trimestres)
        $totalMonthsOffset = ($currentYear * 12 + $startMonth - 1) + ($offset * 3);
        $targetYear  = intdiv($totalMonthsOffset, 12);
        $targetMonth = ($totalMonthsOffset % 12) + 1;

        $months = [];
        for ($i = 0; $i < 3; $i++) {
            $month = $targetMonth + $i;
            $year  = $targetYear;
            if ($month > 12) {
                $month -= 12;
                $year++;
            }
            $months[] = [
                'month' => $month,
                'year'  => $year,
                'name'  => $this->getMonthName($month),
            ];
        }

        return $months;
    }

    private function getMonthCessionData($month, $year, $sltServiceRendu)
    {
        try {
            // ✅ Récupérer UNIQUEMENT les dossiers avec slt_service_rendu = 'Délimitation'
            $dossiers = Dossier::whereMonth('dt_date_creation', $month)
                ->whereYear('dt_date_creation', $year)
                ->where('slt_service_rendu', $sltServiceRendu)
                ->whereNotNull('txt_etat_cession')
                ->where('txt_etat_cession', '!=', '1')
                ->get();
            
            // Tableau pour stocker les différents types d'états de cession
            $etatsCession = [];
            $nombreTotal = 0;
            $montantTotal = 0;
            
            foreach ($dossiers as $dossier) {
                $typeCession = $dossier->txt_etat_cession;
                
                // Extraire le montant si présent dans le champ
                $montant = $this->extractMontantFromCession($typeCession);
                
                if (!isset($etatsCession[$typeCession])) {
                    $etatsCession[$typeCession] = [
                        'type' => $typeCession,
                        'nombre' => 0,
                        'montant_total' => 0,
                        'montant_unitaire' => 0
                    ];
                }
                
                $etatsCession[$typeCession]['nombre']++;
                $etatsCession[$typeCession]['montant_total'] += $montant;
                $nombreTotal++;
                $montantTotal += $montant;
            }
            
            // Calculer les montants unitaires par type
            foreach ($etatsCession as &$etat) {
                if ($etat['nombre'] > 0) {
                    $etat['montant_unitaire'] = round($etat['montant_total'] / $etat['nombre']);
                }
            }
            
            // Trier par type (ordre alphabétique)
            ksort($etatsCession);
            
            // Ajouter une entrée pour le total
            $etatsCession['TOTAL'] = [
                'type' => 'TOTAL',
                'nombre' => $nombreTotal,
                'montant_total' => $montantTotal,
                'montant_unitaire' => $nombreTotal > 0 ? round($montantTotal / $nombreTotal) : 0
            ];
            
            return [
                'montant_etat' => $montantTotal,
                'nombre_unitaire' => $nombreTotal,
                'montant_unitaire' => $nombreTotal > 0 ? round($montantTotal / $nombreTotal) : 0,
                'montant_total' => $montantTotal,
                'details' => $etatsCession // Détails par type d'état de cession
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur dans getMonthCessionData: ' . $e->getMessage());
            return [
                'montant_etat' => 0,
                'nombre_unitaire' => 0,
                'montant_unitaire' => 0,
                'montant_total' => 0,
                'details' => []
            ];
        }
    }

    /**
     * Extraire le montant d'une chaîne d'état de cession
     * Exemple: "Cession définitive 1.500.000" -> 1500000
     */
    private function extractMontantFromCession($cessionText)
    {
        if (empty($cessionText)) {
            return 0;
        }
        
        // Si c'est déjà un nombre
        if (is_numeric($cessionText)) {
            return (float) $cessionText;
        }
        
        // Extraire les nombres de la chaîne
        preg_match_all('/[0-9]+[\s.,]?[0-9]*/', $cessionText, $matches);
        
        if (!empty($matches[0])) {
            // Prendre le dernier nombre trouvé (généralement le montant)
            $lastNumber = end($matches[0]);
            // Nettoyer le nombre (enlever les espaces, points, virgules)
            $cleanNumber = preg_replace('/[^0-9]/', '', $lastNumber);
            if (!empty($cleanNumber)) {
                return (float) $cleanNumber;
            }
        }
        
        return 0;
    }

    /**
     * Obtenir le nom du mois en français
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
            4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
            10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        return $months[$month] ?? 'Inconnu';
    }
    
    /**
     * *
     * *
     * EXTRAIT DE PLAN 
     * 
     * 
     */

    /**
     * Points Densfier 
     */

    // storePoint point densifier 
    public function createPoint()
    {
        // Charge directement les relations
        $points = PointDensifier::with(['departement', 'arrondissement', 'commune'])->get();
        
        // Pour le formulaire (dropdown)
        $departements = Departement::all();   

        return Inertia::render("parametre/pointDensifier/createPoint", [  
            "departements" => $departements, 
            "arrondissements" => [], // à remplir via AJAX plus tard
            "communes" => [], // à remplir via AJAX plus tard
            "point_densifiers" => $points
        ]);
    }

    // editPoint
    public function editPoint($id)
    {
        $point_densifier = PointDensifier::with(['departement', 'arrondissement', 'commune'])->findOrFail($id);

        $departements = Departement::all();
        $arrondissements = Arrondissement::where('id', $point_densifier->slt_departement)->get();
        $communes = Commune::where('id', $point_densifier->slt_arrondissement)->get();

        return Inertia::render("parametre/pointDensifier/editPoint", [
            "point_densifiers" => $point_densifier,
            "departements" => $departements,
            "arrondissements" => $arrondissements,
            "communes" => $communes,
        ]);
    }

    // store
    public function storePoint(Request $request)
    {  
        $validated = $request->validate([
            'txt_nomPoint' => 'required|string|max:255',
            'txt_dateCreationPoint' => 'required|date',
            'slt_departement' => 'required|exists:departements,id',
            'slt_arrondissement' => 'required|exists:arrondissements,id',
            'slt_commune' => 'required|exists:communes,id',
            'txt_descriptionPoint' => 'required|string|max:255',
            'txt_situationPoint' => 'required|string|max:255',
            'txt_estPoint' => 'required|string|max:255',
            'txt_nordPoint' => 'required|string|max:255',
            'txt_hauteurPoint' => 'required|string|max:255',
            'fichierPDFPoint' => 'nullable|file|mimes:pdf|max:102400',
        ],[
            'txt_nomPoint.required' => 'Le nom du point est requis.',
            'txt_dateCreationPoint.required' => 'La date de création est requise.',
            'slt_departement.required' => 'Le département est requis.',
            'slt_arrondissement.required' => 'L\'arrondissement est requis.',
            'slt_commune.required' => 'La commune est requise.',
            'txt_descriptionPoint.required' => 'La description est requise.',
            'txt_situationPoint.required' => 'La situation est requise.',
            'txt_estPoint.required' => 'La coordonnée Est est requise.',
            'txt_nordPoint.required' => 'La coordonnée Nord est requise.',
            'txt_hauteurPoint.required' => 'La hauteur est requise.',
            'fichierPDFPoint.mimes' => 'Le document doit être un fichier PDF.',
            'fichierPDFPoint.max' => 'Le fichier PDF ne doit pas dépasser 100 Mo.',
        ] );

        // 📁 fichier PDF
        if ($request->hasFile('fichierPDFPoint')) {
            $validated['fichierPDFPoint'] = $request->file('fichierPDFPoint')
                ->store('pointDensifier', 'public');
        }
  
        return DB::transaction(function () use ($validated) {
  
            // 🏡 TERRAIN
            PointDensifier::create([
                'txt_nomPoint' => $validated['txt_nomPoint'],
                'txt_dateCreationPoint' => $validated['txt_dateCreationPoint'],
                'slt_departement' => $validated['slt_departement'],
                'slt_arrondissement' => $validated['slt_arrondissement'],
                'slt_commune' => $validated['slt_commune'],
                'txt_descriptionPoint' => $validated['txt_descriptionPoint'],
                'txt_situationPoint' => $validated['txt_situationPoint'],
                'txt_estPoint' => $validated['txt_estPoint'],
                'txt_nordPoint' => $validated['txt_nordPoint'],
                'txt_hauteurPoint' => $validated['txt_hauteurPoint'],
                'fichierPDFPoint' => $validated['fichierPDFPoint'] ?? null,
            ]); 
 

            return redirect()->route('parametre.pointDensifier.createPoint')->with([
                'success' => '✅ Point ' . $validated['txt_nomPoint'] . ' enregistré avec succès !'
            ]);
        });
    }

    
    /**
     * Mettre à jour un point densifié
     */
    public function updatePoint(Request $request, string $id)
    { 
        $point_densifier = PointDensifier::findOrFail($id); 

        $validated = $request->validate([
            'txt_nomPoint' => 'required|string|max:255',
            'txt_dateCreationPoint' => 'required|date',
            'slt_departement' => 'required|exists:departements,id',
            'slt_arrondissement' => 'required|exists:arrondissements,id',
            'slt_commune' => 'required|exists:communes,id',
            'txt_descriptionPoint' => 'required|string|max:255',
            'txt_situationPoint' => 'required|string|max:255',
            'txt_estPoint' => 'required|string|max:255',
            'txt_nordPoint' => 'required|string|max:255',
            'txt_hauteurPoint' => 'required|string|max:255',
            'fichierPDFPoint' => 'nullable|string|max:255',
        ],[
            'txt_nomPoint.required' => 'Le nom du point est requis.',
            'txt_dateCreationPoint.required' => 'La date de création est requise.',
            'slt_departement.required' => 'Le département est requis.',
            'slt_arrondissement.required' => 'L\'arrondissement est requis.',
            'slt_commune.required' => 'La commune est requise.',
            'txt_descriptionPoint.required' => 'La description est requise.',
            'txt_situationPoint.required' => 'La situation est requise.',
            'txt_estPoint.required' => 'La coordonnée Est est requise.',
            'txt_nordPoint.required' => 'La coordonnée Nord est requise.',
            'txt_hauteurPoint.required' => 'La hauteur est requise.'
        ] );
        
        // ✅ GESTION DU FICHIER COMME DANS STORE()
        if ($request->hasFile('fichierPDFPoint')) {
            // Supprimer l'ancien fichier si existe
            if ($point_densifier->fichierPDFPoint && Storage::disk('public')->exists($point_densifier->fichierPDFPoint)) {
                Storage::disk('public')->delete($point_densifier->fichierPDFPoint);
            }
            
            // Stocker le nouveau fichier DANS LE MÊME DOSSIER
            $validateData['fichierPDFPoint'] = $request->file('fichierPDFPoint')->store('pointDensifier', 'public');
        } else {
            // Garder l'ancien fichier si aucun nouveau n'est uploadé
            $validateData['fichierPDFPoint'] = $point_densifier->fichierPDF;
        }

        // ✅ MISE À JOUR COMME DANS STORE()
        $point_densifier->update([
            'txt_nomPoint' => $validated['txt_nomPoint'],
            'txt_dateCreationPoint' => $validated['txt_dateCreationPoint'],
            'slt_departement' => $validated['slt_departement'],
            'slt_arrondissement' => $validated['slt_arrondissement'],
            'slt_commune' => $validated['slt_commune'],
            'txt_descriptionPoint' => $validated['txt_descriptionPoint'],
            'txt_situationPoint' => $validated['txt_situationPoint'],
            'txt_estPoint' => $validated['txt_estPoint'],
            'txt_nordPoint' => $validated['txt_nordPoint'],
            'txt_hauteurPoint' => $validated['txt_hauteurPoint'],
            'fichierPDFPoint' => $validated['fichierPDFPoint'],
        ]);


        return redirect()->route('parametre.pointDensifier.createPoint')
            ->with('success', 'Point densifié mis à jour avec succès.');
    }

    /**
     * Détail des traitements d'un utilisateur sur le trimestre affiché.
     */
    public function getEvaluationUtilisateur(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'annee' => ['nullable', 'integer'],
            'trimestre' => ['nullable', 'integer', 'between:1,4'],
        ]);

        $periode = $this->bornesTrimestre(
            isset($validated['annee']) ? (int) $validated['annee'] : null,
            isset($validated['trimestre']) ? (int) $validated['trimestre'] : null,
        );

        $user = User::query()->findOrFail($validated['user_id']);
        $roleColumn = Schema::hasColumn('users', 'role') ? 'role' : null;
        $hasDetail = Schema::hasColumn('activites_utilisateurs', 'libelle');

        $base = ActiviteUtilisateur::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$periode['debut'], $periode['fin']]);

        $totauxBruts = (clone $base)
            ->select('action', DB::raw('COUNT(*) as total'))
            ->groupBy('action')
            ->pluck('total', 'action');

        $total = (int) $totauxBruts->sum();
        $lignes = (clone $base)
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        $traitements = $lignes->map(function (ActiviteUtilisateur $activite) use ($hasDetail) {
            $libelle = $hasDetail ? $activite->libelle : null;

            return [
                'date' => optional($activite->created_at)->format('d/m/Y H:i'),
                'action' => $activite->action,
                'module' => $hasDetail ? ($activite->module ?: null) : null,
                'libelle' => $libelle ?: 'Traitement non détaillé',
                'reference' => $hasDetail ? ($activite->reference ?: null) : null,
                'chemin' => $hasDetail ? ($activite->chemin ?: null) : null,
            ];
        })->values();

        return response()->json([
            'user_id' => $user->id,
            'utilisateur' => $user->name,
            'role' => $roleColumn ? ($user->{$roleColumn} ?: '—') : '—',
            'trimestre' => $periode['label'],
            'totaux' => [
                'enregistrements' => (int) ($totauxBruts['enregistrement'] ?? 0),
                'modifications' => (int) ($totauxBruts['modification'] ?? 0),
                'recherches' => (int) ($totauxBruts['recherche'] ?? 0),
            ],
            'total' => $total,
            'tronque' => $total > $traitements->count(),
            'traitements' => $traitements,
        ]);
    }

    /**
     * Évaluation des utilisateurs pour un trimestre.
     * Score = enregistrements × 3 + modifications × 2 + recherches × 1.
     */
    private function getEvaluationUtilisateurs(?int $annee = null, ?int $trimestre = null): array
    {
        $periode = $this->bornesTrimestre($annee, $trimestre);
        $annee = $periode['annee'];
        $trimestre = $periode['trimestre'];
        $debut = $periode['debut'];
        $fin = $periode['fin'];
        $label = $periode['label'];

        $roleColumn = Schema::hasColumn('users', 'role') ? 'role' : null;

        $columns = ['id', 'name'];
        if ($roleColumn) {
            $columns[] = $roleColumn;
        }

        $users = User::query()->orderBy('name')->get($columns);

        $counts = collect();
        if (Schema::hasTable('activites_utilisateurs')) {
            $counts = ActiviteUtilisateur::query()
                ->select('user_id', 'action', DB::raw('COUNT(*) as total'))
                ->whereBetween('created_at', [$debut, $fin])
                ->groupBy('user_id', 'action')
                ->get()
                ->groupBy('user_id');
        }

        $lignes = $users->map(function ($user) use ($counts, $label, $roleColumn) {
            $byAction = collect($counts->get($user->id, []))->keyBy('action');
            $enregistrements = (int) ($byAction->get('enregistrement')->total ?? 0);
            $modifications = (int) ($byAction->get('modification')->total ?? 0);
            $recherches = (int) ($byAction->get('recherche')->total ?? 0);
            $score = ($enregistrements * 3) + ($modifications * 2); //+ $recherches;

            return [
                'user_id' => $user->id,
                'utilisateur' => $user->name,
                'role' => $roleColumn ? ($user->{$roleColumn} ?: '—') : '—',
                'trimestre' => $label,
                'enregistrements' => $enregistrements,
                'modifications' => $modifications,
                'recherches' => $recherches,
                'score' => $score,
                'appreciation' => $this->appreciationUtilisateur($score),
            ];
        })->sortByDesc('score')->values()->all();

        return [
            'annee' => $annee,
            'trimestre' => $trimestre,
            'label' => $label,
            'lignes' => $lignes,
        ];
    }

    private function bornesTrimestre(?int $annee, ?int $trimestre): array
    {
        $annee = $annee && $annee > 2000 ? $annee : (int) now()->year;
        $trimestre = $trimestre && $trimestre >= 1 && $trimestre <= 4
            ? $trimestre
            : (int) ceil(now()->month / 3);

        $debut = Carbon::create($annee, (($trimestre - 1) * 3) + 1, 1)->startOfDay();
        $fin = $debut->copy()->addMonths(3)->subSecond();

        return [
            'annee' => $annee,
            'trimestre' => $trimestre,
            'debut' => $debut,
            'fin' => $fin,
            'label' => ['1er', '2e', '3e', '4e'][$trimestre - 1].' trimestre '.$annee,
        ];
    }

    private function appreciationUtilisateur(int $score): string
    {
        return match (true) {
            $score >= 50 => 'Excellente',
            $score >= 25 => 'Bonne',
            $score >= 10 => 'Moyenne',
            $score >= 1 => 'Faible',
            default => 'Aucune activité',
        };
    }
}
