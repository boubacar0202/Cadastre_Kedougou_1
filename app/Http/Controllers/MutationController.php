<?php

namespace App\Http\Controllers;
 
use App\Models\Dossier; 
use App\Models\Mutation; 
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use App\Models\Titulaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    // public function verify(Request $request)
    // {
    //     $request->validate([
    //         'txt_num_dossier' => 'required|string',
    //     ],[
    //         'txt_num_dossier.required' => '❌ Entrer le Numéro dossier',
    //     ]);
    
    //     $dossier = Dossier::where('txt_num_dossier', $request->txt_num_dossier)->first();
    //     $terrain = $dossier ? Terrain::where('txt_num_dossier', $dossier->id)->first() : null;
 
    //     if ($dossier) {
    //         // Pas de redirection — juste retour d'un flag JSON
    //         return response()->json([
    //             'success' => '✅ Numéro Dossier trouvé !',
    //             'exists' => true,
    //             'terrain' => $terrain,
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'errors' => [
    //                 'txt_num_dossier' => '❌ Numéro dossier introuvable.'
    //             ]
    //         ], 422);
    //     }
    // } 

    public function fetchReferencesLotissement(Request $request): JsonResponse
    { 
        // Récupérer tous les terrains avec un lotissement non vide
        $terrains = Terrain::whereNotNull('txt_lotissement')
                        ->where('txt_lotissement', '!=', '')
                        ->get();
    
        // Extraire les références de lotissement (sans doublons)
        $referencesLot = $terrains->pluck('txt_lotissement')
                                ->unique()
                                ->values()
                                ->all(); 
    
        return response()->json([
            'referencesLot' => $referencesLot, 
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
        // dd($request->all());
        $request->merge([
            'slt_categoriePR' => (string) $request->slt_categoriePR,
        ]);

        $validatedData = $request->validate([
        //table Reference_usage
        'txt_num_dossier' => 'required|string',
        'txt_nicad' => 'required|string',
        
        'txt_numeroMutation' => 'required|string',
        // information titulaire
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
            // Personne  morale
        'slt_categoriePM_MT' => 'nullable|string',
        'txt_formJuridiquePM_MT' => 'nullable|string',
        'txt_denominationPM_MT' => 'nullable|string',
        'txt_nineaPM_MT' => 'nullable|string',
        'txt_perssonneRepresentantPM_MT' => 'nullable|string',
        'txt_fonctionPM_MT' => 'nullable|string',
        'txt_telephonePM_MT:' => 'nullable|string',
        'txt_emailPM_MT' => 'nullable|string',
        'txt_adressePM_MT' => 'nullable|string',
            // Etat
        'slt_etablissementPA_MT' => 'nullable|string',
        'txt_personneResponsablePA_MT' => 'nullable|string',
        'txt_fonctionResponsablePA_MT' => 'nullable|string',
        'txt_telephonePA_MT' => 'nullable|string',
        'txt_emailPA_MT' => 'nullable|string',
        'txt_ministèreTutelePA_MT' => 'nullable|string',
        // information ecquereur 
        'slt_titulaireMutation' => 'required|string',
        'txt_prenomMutation' => 'nullable|string',
        'txt_nomMT' => 'nullable|string',
        'txt_nationaliteMutation' => 'nullable|string',
        'slt_civiliteMutation' => 'nullable|string',
        'slt_pieceMutation' => 'nullable|string',
        'txt_numPieceMutation' => 'nullable|string',
        'dt_date_delivranceMutation' => 'nullable|date',
        'dt_date_naissanceMutation' => 'nullable|date',
        'txt_lieu_naissanceMutation' => 'nullable|string',
        'txt_adresseMutation' => 'nullable|string',
        'tel_telephoneMutation' => 'nullable|string', 
        // 'txt_representantMutation' => 'nullable|string',
        // 'tel_telRepresentantMutation' => 'nullable|string',
        'txt_typeActeMutation' => 'required|string',
        'txt_nomNotaireMutation' => 'nullable|string',
        'dt_dateMutation' => 'required|date',
        'txt_jugementMutation' => 'nullable|string',
        'txt_observationMutation' => 'nullable|string',
            // Personne physique
        'slt_categoriePM_Mutation'  =>  'nullable|string',
        'txt_formJuridiquePM_Mutation'  =>  'nullable|string',
        'txt_denominationPM_Mutation'   =>  'nullable|string',
        'txt_nineaPM_Mutation'  =>  'nullable|string',
        'txt_perssonneRepresentantPM_Mutation'  =>  'nullable|string',
        'txt_fonctionPM_Mutation'   =>  'nullable|string',
        'txt_telephonePM_Mutation'  =>  'nullable|string',
        'txt_emailPM_Mutation'  =>  'nullable|string',
        'txt_adressePM_Mutation'    =>  'nullable|string',
            // Personne morale
        'slt_etablissementPA_MT'    =>  'nullable|string',
        'txt_personneResponsablePA_Mutation'    =>  'nullable|string',
        'txt_fonctionResponsablePA_Mutation'    =>  'nullable|string',
        'txt_telephonePA_Mutation'  =>  'nullable|string',
        'txt_emailPA_Mutation'  =>  'nullable|string',
        'txt_ministèreTutelePA_Mutation'    =>  'nullable|string',

        'fichierPDFMutation',
 
        ],[
            'txt_num_dossier.required' => '❌ Entrer le Numéro dossier',
            'txt_nicad.required' => '❌ vérifie si le dossier a un nicad', 
            'slt_titulaireMT.required' => '❌ Entrer le Titulaire',
            'slt_titulaireMutation.required' => '❌ Entrer l\'Acquéreur',
            'txt_typeActeMutation.required' => '❌ Entrer le type d\'acte',
            'dt_dateMutation.required' => '❌ Entrer la date de mutation',
            'txt_emailPM_Mutation.email' => '❌ Entrer un adresse email valide',
            'txt_emailPA_Mutation.email' => '❌ Entrer un adresse email valide',
              
            'fichierPDFMutation.file' => 'Le fichier sélectionné est invalide.',
            'fichierPDFMutation.mimes' => 'Le fichier doit être au format PDF.',
            'fichierPDFMutation.max' => 'La taille du fichier ne doit pas dépasser 120 Mo.', 
        ]); 

        // traitement fichier PDF
        if ($request->hasFile('fichierPDFMutation')) {
            $validatedData['fichierPDFMutation'] = $request->file('fichierPDFMutation')->store('mutations', 'public');
        }
        $numeroMutation = $validatedData['txt_num_dossier'];
        $nicadMutation = $validatedData['txt_nicad'];

        Mutation::create([
            'txt_numeroMutation' => $numeroMutation,
            'txt_nicadMutation' => $nicadMutation,
            // information titulaire
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
            'txt_telephonePM_MT:' => $validatedData['txt_telephonePM_MT'] ?? null,
            'txt_emailPM_MT' => $validatedData['txt_emailPM_MT'] ?? null,
            'txt_adressePM_MT' => $validatedData['txt_adressePM_MT'] ?? null,
                // Etat
            'slt_etablissementPA_MT' => $validatedData['slt_etablissementPA_MT'] ?? null,
            'txt_personneResponsablePA_MT' => $validatedData['txt_personneResponsablePA_MT'] ?? null,
            'txt_fonctionResponsablePA_MT' => $validatedData['txt_fonctionResponsablePA_MT'] ?? null,
            'txt_telephonePA_MT' => $validatedData['txt_telephonePA_MT'] ?? null,
            'txt_emailPA_MT' => $validatedData['txt_emailPA_MT'] ?? null,
            'txt_ministèreTutelePA_MT' => $validatedData['txt_ministèreTutelePA_MT'] ?? null,

            // information ecquereur 
            'slt_titulaireMutation' => $validatedData['slt_titulaireMutation'],
            'txt_prenomMutation' => $validatedData['txt_prenomMutation'],
            'txt_nomMT' => $validatedData['txt_nomMT'],
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
            'txt_jugementMutation' => $validatedData['txt_jugementMutation'] ?? null,
            'txt_observationMutation' => $validatedData['txt_observationMutation'] ?? null,

            'fichierPDFMutation' => $validatedData['fichierPDFMutation'] ?? null,
        ]); 
 
 
        return redirect()->back()->with('success', '✅ Donnée enregistrée !');
 
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
