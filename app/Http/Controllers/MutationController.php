<?php

namespace App\Http\Controllers;
 
use App\Models\Dossier;
use App\Models\Lotissement;
use App\Models\Mutation; 
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use App\Models\Titulaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
class MutationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $terrains = Terrain::all();

        return Inertia::render("mutation/create", [
            "terrains" => $terrains,  
        ]);
    }

    /**
     * verifier dossier exist
    */ 

    public function fetchReferencesLotissement(Request $request): JsonResponse
    { 
        // Récupérer tous les lotissement non vide
        $Lotissements = Lotissement::whereNotNull('txt_denomination')
                        ->where('txt_denomination', '!=', '')
                        ->get();
         
        // Extraire les références de lotissement (sans doublons)
        $referencesLot = $Lotissements->pluck('txt_denomination')
                                        ->unique()
                                        ->values()
                                        ->all(); 
    
        return response()->json([
            'referencesLot' => $referencesLot, 
        ]);
    }
 
    // verifier terrain exist
    public function verifyLotissement(Request $request)
    {
        $request->validate([
            'txt_num_lotissement' => 'required|string',
            'txt_lotissement' => 'required|string',
        ]);

        // 1️⃣ Chercher terrain
        $terrain = Terrain::where('txt_num_lotissement', $request->txt_num_lotissement)
            ->where('txt_lotissement', $request->txt_lotissement)
            ->first();
 
        if (!$terrain) {
            return response()->json([
                'exists' => false,
                'message' => 'Terrain introuvable, enregistrer le terrain avant de faire une mutation.'
            ]);
        }

        // 2️⃣ Chercher titulaire relié
        $titulaire = Titulaire::find($terrain->titulaire_id);
        $dossier = Dossier::where('id', $terrain->txt_num_dossier)->first();
        
 
        return response()->json([
            'exists'    => true,
            'terrain'   => $terrain,
            'titulaire' => $titulaire,
            'dossier'   => $dossier,
            'message'   => '✅ Terrain et titulaire chargés !'
        ]);
    }

 
    /**
     * Store a newly created resource in storage.
     */
    // vérifie si le nicad existe dans la base de données 
    public function verifierNicad($nicad)
    {
        $existe = ReferenceUsage::where('txt_nicad', $nicad)->exists();

        return response()->json(['existe' => $existe]);
    } 
 
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    { 
        $request->merge([
            'slt_categoriePR' => (string) $request->slt_categoriePR,
        ]);

        $validatedData = $request->validate([  
        // information titulaire
        'txt_num_lotissement' => 'required|string',
        'txt_lotissement' => 'required|string',
        'slt_titulaireMT' => 'required|string',
        'txt_nationaliteMT' => 'nullable|string',
        'slt_civiliteMT' => 'nullable|string',
        'txt_prenomMT' => 'nullable|string',
        'txt_nomMT' => 'nullable|string',
        'slt_pieceMT' => 'nullable|string',
        'txt_numPieceMT' => 'nullable|string',
        'dt_date_delivranceMT' => 'date|string',
        'dt_date_naissanceMT' => 'date|string',
        'txt_lieu_naissanceMT' => 'required|string',
        'txt_adresseMT' => 'nullable|string',
        'tel_telephoneMT' => 'nullable|string',  
        'txt_representantMT' => 'nullable|string',
        'tel_telRepresentantMT' => 'nullable|string', 
            // 
        'slt_categoriePM_MT' => 'nullable|string',
        'txt_formJuridiquePM_MT' => 'nullable|string',
        'txt_denominationPM_MT' => 'nullable|string',
        'txt_nineaPM_MT' => 'nullable|string',
        'txt_perssonneRepresentantPM_MT' => 'nullable|string',
        'txt_fonctionPM_MT' => 'nullable|string',
        'txt_telephonePM_MT:' => 'nullable|string',
        'txt_emailPM_MT' => 'nullable|string',
        'txt_adressePM_MT' => 'nullable|string',
            //  
        'slt_etablissementPA_MT' => 'nullable|string',
        'txt_personneResponsablePA_MT' => 'nullable|string',
        'txt_fonctionResponsablePA_MT' => 'nullable|string',
        'txt_telephonePA_MT' => 'nullable|string',
        'txt_emailPA_MT' => 'nullable|string',
        'txt_ministèreTutelePA_MT' => 'nullable|string',
        // information ecquereur 
        'slt_titulaireMutation' => 'required|string',
        'txt_prenomMutation' => 'nullable|string',
        'txt_nomMutation' => 'nullable|string',
        'txt_nationaliteMutation' => 'nullable|string',
        'slt_civiliteMutation' => 'nullable|string',
        'slt_pieceMutation' => 'nullable|string',
        'txt_numPieceMutation' => 'nullable|string',
        'dt_date_delivranceMutation' => 'nullable|date',
        'dt_date_naissanceMutation' => 'nullable|date',
        'txt_lieu_naissanceMutation' => 'nullable|string',
        'txt_adresseMutation' => 'nullable|string',
        'tel_telephoneMutation' => 'nullable|string', 
            //   
        'txt_typeActeMutation' => 'required|string',
        'txt_nomNotaireMutation' => 'nullable|string',
        'dt_dateMutation' => 'required|date',
        'txt_natureMutation' => 'nullable|string',
        'txt_observationMutation' => 'nullable|string',
            //  
        'slt_categoriePM_Mutation'  =>  'nullable|string',
        'txt_formJuridiquePM_Mutation'  =>  'nullable|string',
        'txt_denominationPM_Mutation'   =>  'nullable|string',
        'txt_nineaPM_Mutation'  =>  'nullable|string',
        'txt_perssonneRepresentantPM_Mutation'  =>  'nullable|string',
        'txt_fonctionPM_Mutation'   =>  'nullable|string',
        'txt_telephonePM_Mutation'  =>  'nullable|string',
        'txt_emailPM_Mutation'  =>  'nullable|string',
        'txt_adressePM_Mutation'    =>  'nullable|string',
            //  
        'slt_etablissementPA_Mutation'    =>  'nullable|string',
        'txt_personneResponsablePA_Mutation'    =>  'nullable|string',
        'txt_fonctionResponsablePA_Mutation'    =>  'nullable|string',
        'txt_telephonePA_Mutation'  =>  'nullable|string',
        'txt_emailPA_Mutation'  =>  'nullable|string',
        'txt_ministèreTutelePA_Mutation'    =>  'nullable|string',

        'fichierPDFMutation',
 
        ],[ 
            'slt_titulaireMT.required' => '❌ Entrer les informations du Titulaire',
            'slt_titulaireMutation.required' => '❌ Entrer les informations de l\'Acquéreur',
            'txt_typeActeMutation.required' => '❌ Entrer le type d\'acte',
            'dt_dateMutation.required' => '❌ Entrer la date du mutation',
            'txt_emailPM_Mutation.email' => '❌ Entrer un adresse email valide',
            'txt_emailPA_Mutation.email' => '❌ Entrer un adresse email valide',
              
            'fichierPDFMutation.file' => 'Le fichier sélectionné est invalide.',
            'fichierPDFMutation.mimes' => 'Le fichier doit être au format PDF.',
            'fichierPDFMutation.max' => 'La taille du fichier ne doit pas dépasser 120 Mo.', 
        ]); 

        // traitement fichier PDF
        $terrain = Terrain::with('dossier')
            ->where('txt_num_lotissement', $request->txt_num_lotissement)
            ->where('txt_lotissement', $request->txt_lotissement)
            ->first();

        if (!$terrain || !$terrain->dossier) {
            return back()->withErrors(['terrain' => 'Terrain ou dossier introuvable']);
        }

        $numeroMutation = $terrain->dossier->txt_num_dossier;
        $nicadMutation  = $terrain->txt_nicad;
 
        Mutation::create([
            'txt_numeroMutation' => $numeroMutation,
            'txt_nicadMutation' => $nicadMutation,

            'txt_num_lotissement' => $validatedData['txt_num_lotissement'],
            'txt_lotissement' => $validatedData['txt_lotissement'],

            // Titulaire  
            'slt_titulaireMT' => $validatedData['slt_titulaireMT'],
            'txt_nationaliteMT' => $validatedData['txt_nationaliteMT'] ?? null,
            'slt_civiliteMT' => $validatedData['slt_civiliteMT'] ?? null,
            'txt_prenomMT' => $validatedData['txt_prenomMT'] ?? null,
            'txt_nomMT' => $validatedData['txt_nomMT'] ?? null,
            'slt_pieceMT' => $validatedData['slt_pieceMT'] ?? null,
            'txt_numPieceMT' => $validatedData['txt_numPieceMT'] ?? null,
            'dt_date_delivranceMT' => $validatedData['dt_date_delivranceMT'] ?? null,
            'dt_date_naissanceMT' => $validatedData['dt_date_naissanceMT'] ?? null,
            'txt_lieu_naissanceMT' => $validatedData['txt_lieu_naissanceMT'] ?? null,
            'txt_adresseMT' => $validatedData['txt_adresseMT'] ?? null,
            'tel_telephoneMT' => $validatedData['tel_telephoneMT'] ?? null,  
            'txt_representantMT' => $validatedData['txt_representantMT'] ?? null,
            'tel_telRepresentantMT' => $validatedData['tel_telRepresentantMT'] ?? null,
                // Personne  morale
            'slt_categoriePM_MT' => $validatedData['slt_categoriePM_MT'] ?? null,
            'txt_formJuridiquePM_MT' => $validatedData['txt_formJuridiquePM_MT'] ?? null,
            'txt_denominationPM_MT' => $validatedData['txt_denominationPM_MT'] ?? null,
            'txt_nineaPM_MT' => $validatedData['txt_nineaPM_MT'] ?? null,
            'txt_perssonneRepresentantPM_MT' => $validatedData['txt_perssonneRepresentantPM_MT'] ?? null,
            'txt_fonctionPM_MT' => $validatedData['txt_fonctionPM_MT'] ?? null,
            'txt_telephonePM_MT' => $validatedData['txt_telephonePM_MT'] ?? null,
            'txt_emailPM_MT' => $validatedData['txt_emailPM_MT'] ?? null,
            'txt_adressePM_MT' => $validatedData['txt_adressePM_MT'] ?? null,
                // Etat
            'slt_etablissementPA_MT' => $validatedData['slt_etablissementPA_MT'] ?? null,
            'txt_personneResponsablePA_MT' => $validatedData['txt_personneResponsablePA_MT'] ?? null,
            'txt_fonctionResponsablePA_MT' => $validatedData['txt_fonctionResponsablePA_MT'] ?? null,
            'txt_telephonePA_MT' => $validatedData['txt_telephonePA_MT'] ?? null,
            'txt_emailPA_MT' => $validatedData['txt_emailPA_MT'] ?? null,
            'txt_ministèreTutelePA_MT' => $validatedData['txt_ministèreTutelePA_MT'] ?? null,

            // Acquereur 
            'slt_titulaireMutation' => $validatedData['slt_titulaireMutation'],
            'txt_prenomMutation' => $validatedData['txt_prenomMutation'],
            'txt_nomMutation' => $validatedData['txt_nomMutation'],
            'txt_nationaliteMutation' => $validatedData['txt_nationaliteMutation'],
            'slt_civiliteMutation' => $validatedData['slt_civiliteMutation'],
            'slt_pieceMutation' => $validatedData['slt_pieceMutation'],
            'txt_numPieceMutation' => $validatedData['txt_numPieceMutation'],
            'dt_date_delivranceMutation' => $validatedData['dt_date_delivranceMutation'],
            'dt_date_naissanceMutation' => $validatedData['dt_date_naissanceMutation'],
            'txt_lieu_naissanceMutation' => $validatedData['txt_lieu_naissanceMutation'],
            'txt_adresseMutation' => $validatedData['txt_adresseMutation'],
            'tel_telephoneMutation' => $validatedData['tel_telephoneMutation'],
            'txt_typeActeMutation' => $validatedData['txt_typeActeMutation'] ?? null,
            'txt_nomNotaireMutation' => $validatedData['txt_nomNotaireMutation'] ?? null,
            'dt_dateMutation' => $validatedData['dt_dateMutation'] ?? null,
            'txt_natureMutation' => $validatedData['txt_natureMutation'] ?? null,
            'txt_observationMutation' => $validatedData['txt_observationMutation'] ?? null,
                // Personne physique
            'slt_categoriePM_Mutation'  => $validatedData['slt_categoriePM_Mutation'] ?? null,
            'txt_formJuridiquePM_Mutation'  =>  $validatedData['txt_formJuridiquePM_Mutation'] ?? null,
            'txt_denominationPM_Mutation'   =>  $validatedData['txt_denominationPM_Mutation'] ?? null,
            'txt_nineaPM_Mutation'  =>  $validatedData['txt_nineaPM_Mutation'] ?? null,
            'txt_perssonneRepresentantPM_Mutation'  =>  $validatedData['txt_perssonneRepresentantPM_Mutation'] ?? null,
            'txt_fonctionPM_Mutation'   =>  $validatedData['txt_fonctionPM_Mutation'] ?? null,
            'txt_telephonePM_Mutation'  =>  $validatedData['txt_telephonePM_Mutation'] ?? null,
            'txt_emailPM_Mutation'  =>  $validatedData['txt_emailPM_Mutation'] ?? null,
            'txt_adressePM_Mutation'    =>  $validatedData['txt_adressePM_Mutation'] ?? null,
                // Personne morale
            'slt_etablissementPA_Mutation'    =>  $validatedData['slt_etablissementPA_Mutation'] ?? null,
            'txt_personneResponsablePA_Mutation'    =>  $validatedData['txt_personneResponsablePA_Mutation'] ?? null,
            'txt_fonctionResponsablePA_Mutation'    =>  $validatedData['txt_fonctionResponsablePA_Mutation'] ?? null,
            'txt_telephonePA_Mutation'  =>  $validatedData['txt_telephonePA_Mutation'] ?? null,
            'txt_emailPA_Mutation'  =>  $validatedData['txt_emailPA_Mutation'] ?? null,
            'txt_ministèreTutelePA_Mutation'    =>  $validatedData['txt_ministèreTutelePA_Mutation'] ?? null,

            'fichierPDFMutation' => $validatedData['fichierPDFMutation'] ?? null,
        ]); 

        // table titulaires a alimenter 
        // Titulaire::create([
        //     'slt_titulaire' => $validatedData['slt_titulaireMutation'] ?? null,
        //     'txt_nationalite' => $validatedData['txt_nationaliteMutation'] ?? null,
        //     'slt_civilite' => $validatedData['slt_civiliteMutation'] ?? null,
        //     'txt_prenom' => $validatedData['txt_prenomMutation'] ?? null,
        //     'txt_nom' => $validatedData['txt_nomMutation'] ?? null,
        //     'slt_piece' => $validatedData['slt_pieceMutation'] ?? null,
        //     'txt_numPiece' => $validatedData['txt_numPieceMutation'] ?? null,
        //     'dt_date_delivrance' => $validatedData ['dt_date_delivranceMutation'] ?? null,
        //     'dt_date_naissance' => $validatedData ['dt_date_naissanceMutation'] ?? null,
        //     'txt_lieu_naissance' => $validatedData['txt_lieu_naissanceMutation'] ?? null,
        //     'txt_adresse' => $validatedData['txt_adresseMutation'] ?? null,
        //     'tel_telephone' => $validatedData['tel_telephoneMutation'] ?? null,
        //         // Personne physique
        //     'slt_categoriePM'   => $validatedData['slt_categoriePM_Mutation'] ?? null,
        //     'txt_formJuridiquePM' => $validatedData['txt_formJuridiquePM_Mutation'] ?? null, 
        //     'txt_denominationPM' => $validatedData['txt_denominationPM_Mutation'] ?? null, 
        //     'txt_nineaPM' => $validatedData['txt_nineaPM_Mutation'] ?? null, 
        //     'txt_perssonneRepresentantPM' => $validatedData['txt_perssonneRepresentantPM_Mutation'] ?? null, 
        //     'txt_fonctionPM' => $validatedData['txt_fonctionPM_Mutation'] ?? null, 
        //     'txt_telephonePM' => $validatedData['txt_telephonePM_Mutation'] ?? null, 
        //     'txt_emailPM' => $validatedData['txt_emailPM_Mutation'] ?? null, 
        //     'txt_adressePM' => $validatedData['txt_adressePM_Mutation'] ?? null, 
        //         // Personne morale
        //     'slt_etablissementPA' => $validatedData['slt_etablissementPA_Mutation'] ?? null,
        //     'txt_personneResponsablePA' => $validatedData['txt_personneResponsablePA_Mutation'] ?? null,
        //     'txt_fonctionResponsablePA' => $validatedData['txt_fonctionResponsablePA_Mutation'] ?? null,
        //     'txt_telephonePA' => $validatedData['txt_telephonePA_Mutation'] ?? null,
        //     'txt_emailPA' => $validatedData['txt_emailPA_Mutation'] ?? null,
        //     'txt_ministèreTutelePA' => $validatedData['txt_ministèreTutelePA_Mutation'] ?? null,
 
        // ]);
  
        return redirect()->back()->with('success', '✅ Données enregistrées !');
 
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
    public function editMutation(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateMutation(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyMutation(string $id)
    {
        //
    }
}
