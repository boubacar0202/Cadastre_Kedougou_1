<?php

namespace App\Http\Controllers;
 
use App\Models\Arrondissement;
use App\Models\CodeAcces;
use App\Models\Commune;
use App\Models\Departement;
use App\Models\Lotissement;
use App\Models\Mutation;
use Illuminate\Http\Request;
use App\Models\Parametre; 
use App\Models\Terrain;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        //
        $user = User::all();  
        return Inertia::render("parametre/admin", [ 
            'users' => $user,
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
        $terrains = Terrain::with(['region', 'departement', 'arrondissement', 'commune', 'dossier'])->get(); 
        return Inertia::render("parametre/user",
        [
            "terrains" => $terrains
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

    public function adminEdit($id)
    {
        $user = User::findOrFail($id);

        return Inertia::render('parametre/adminEdit', [
            'user' => $user
        ]);
    }


    // Modifier un utilisateur
    public function editUser($id)
    {
        $user = User::findOrFail($id); 
 
        return Inertia::render('parametre/adminEdit', [
            'user' => $user
        ]);
    } 

    // Modifier un utilisateur
    public function updateUser(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Validation
        $validateData = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
        ]);

        // Mise à jour
        $user->update($validateData);

        return redirect()
            ->route('parametre.admin')
            ->with('success', 'Utilisateur ' . $user->name . ' modifié avec succès');
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

        // Récupérer l'utilisateur  
        $user = User::findOrFail($id);

        // Interdire la suppression si est l'email
        if ($user->email === 'mbengueboubacar095@gmail.com' || $user->email === 'fayesaliou196@gmail.com') {
            return response()->json([
                'success' => false,
                'message' => "Impossible : {$user->name} est un administrateur principal."
            ], 403);
        }

        // Supprimer l'utilisateur
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "{$user->name}; supprimé avec succès."
        ]);
    }
 
}
