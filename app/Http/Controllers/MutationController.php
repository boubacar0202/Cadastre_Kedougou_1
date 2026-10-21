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
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class MutationController extends Controller
{
    public function index() {
        $mutation = Mutation::all();  
        return Inertia::render("mutation/index", [ 
            "mutations" => $mutation
        ]); 
    }

    public function create()
    {
        $terrains = Terrain::all();

        return Inertia::render("mutation/create", [
            "terrains" => $terrains,
        ]);
    }

    // ============================================================
    // RÉFÉRENCES LOTISSEMENT
    // ============================================================
    public function fetchReferencesLotissement(Request $request): JsonResponse
    {
        $Lotissements = Lotissement::whereNotNull('txt_denomination')
            ->where('txt_denomination', '!=', '')
            ->get();

        $referencesLot = $Lotissements->pluck('txt_denomination')
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'referencesLot' => $referencesLot,
        ]);
    }

    // ============================================================
    // VÉRIFICATION TERRAIN
    // ============================================================
    public function verifyLotissement(Request $request)
    {
        $request->validate([
            'txt_num_lotissement' => 'required|string',
            'txt_lotissement' => 'required|string',
        ]);

        $terrain = Terrain::where('txt_num_lotissement', $request->txt_num_lotissement)
            ->where('txt_lotissement', $request->txt_lotissement)
            ->first();

        if (!$terrain) {
            return response()->json([
                'exists' => false,
                'message' => 'Terrain introuvable, enregistrer le terrain avant de faire une mutation.'
            ]);
        }

        $titulaire = Titulaire::find($terrain->titulaire_id);
        $dossier = Dossier::where('id', $terrain->txt_num_dossier)->first();

        // 🔍 Vérifier si une mutation existe déjà pour ce terrain
        /*$mutationExistante = Mutation::where('txt_num_lotissementMutation', $terrain->txt_num_lotissement)
            ->where('txt_lotissementMutation', $terrain->txt_lotissement)
            ->exists();

        $mutationExistante = Mutation::where('txt_numeroMutation', $terrain->txt_num_dossier)->exists();
        */

        return response()->json([
            'exists'    => true,
            //'duplicate' => $mutationExistante,
            'terrain'   => $terrain,
            'titulaire' => $titulaire,
            'dossier'   => $dossier,
            /*'message'   => $mutationExistante
                ? '⚠️ Une mutation existe déjà pour ce terrain.'
                : '✅ Terrain et titulaire chargés !'
                */
            'message'   => '✅ Terrain et titulaire chargés !'
        ]);
    }

    public function verifierNicad($nicad)
    {
        $existe = ReferenceUsage::where('txt_nicad', $nicad)->exists();
        return response()->json(['existe' => $existe]);
    }

    // ============================================================
    // STORE
    // ============================================================
    public function store(Request $request)
    {
        // Normalisation catégorie
        if ($request->has('slt_categoriePR')) {
            $request->merge([
                'slt_categoriePR' => (string) $request->slt_categoriePR,
            ]);
        }

        // ✅ Validation COHÉRENTE avec la vue (noms corrigés)
        $validatedData = $request->validate([
            // Terrain
            'txt_num_lotissementMutation' => 'required|string',
            'txt_lotissementMutation'     => 'required|string',
            'txt_num_dossier'             => 'nullable|string',

            // ---- Titulaire ----
            'slt_titulaireMT'             => 'required|string',
            'txt_nationaliteMT'           => 'nullable|string',
            'slt_civiliteMT'              => 'nullable|string',
            'txt_prenomMT'                => 'nullable|string',
            'txt_nomMT'                   => 'nullable|string',
            'slt_pieceMT'                 => 'nullable|string',
            'txt_numPieceMT'              => 'nullable|string',
            'dt_date_delivranceMT'        => 'nullable|date',
            'dt_date_naissanceMT'         => 'nullable|date',
            'txt_lieu_naissanceMT'        => 'nullable|string',
            'txt_adresseMT'               => 'nullable|string',
            'tel_telephoneMT'             => 'nullable|string',
            'txt_representantMT'          => 'nullable|string',
            'tel_telRepresentantMT'       => 'nullable|string',

            // Personne morale (titulaire) — NOM CORRIGÉ (1 seul "s")
            'slt_categoriePM_MT'                  => 'nullable|string',
            'txt_formJuridiquePM_MT'              => 'nullable|string',
            'txt_denominationPM_MT'               => 'nullable|string',
            'txt_nineaPM_MT'                      => 'nullable|string',
            'txt_personneRepresentantPM_MT'       => 'nullable|string',
            'txt_fonctionPM_MT'                   => 'nullable|string',
            'txt_telephonePM_MT'                  => 'nullable|string',  // 🔧 corrigé ":" en trop
            'txt_emailPM_MT'                      => 'nullable|email',
            'txt_adressePM_MT'                    => 'nullable|string',

            // État (titulaire) — NOM CORRIGÉ (sans accent)
            'slt_etablissementPA_MT'              => 'nullable|string',
            'txt_personneResponsablePA_MT'        => 'nullable|string',
            'txt_fonctionResponsablePA_MT'        => 'nullable|string',
            'txt_telephonePA_MT'                  => 'nullable|string',
            'txt_emailPA_MT'                      => 'nullable|email',
            'txt_ministereTutelePA_MT'            => 'nullable|string',

            // ---- Acquéreur ----
            'slt_titulaireMutation'          => 'required|string',
            'txt_prenomMutation'             => 'nullable|string',
            'txt_nomMutation'                => 'nullable|string',
            'txt_nationaliteMutation'        => 'nullable|string',
            'slt_civiliteMutation'           => 'nullable|string',
            'slt_pieceMutation'              => 'nullable|string',
            'txt_numPieceMutation'           => 'nullable|string',
            'dt_date_delivranceMutation'     => 'nullable|date',
            'dt_date_naissanceMutation'      => 'nullable|date',
            'txt_lieu_naissanceMutation'     => 'nullable|string',
            'txt_adresseMutation'            => 'nullable|string',
            'tel_telephoneMutation'          => 'nullable|string',
            'txt_representantMutation'       => 'nullable|string',
            'tel_telRepresentantMutation'    => 'nullable|string',

            // Acte
            'txt_typeActeMutation'        => 'required|string',
            'txt_nomNotaireMutation'      => 'nullable|string',
            'dt_dateMutation'             => 'required|date',
            'txt_natureMutation'          => 'nullable|string',
            'txt_jugementMutation'        => 'nullable|string',
            'txt_observationMutation'     => 'nullable|string',

            // Personne morale (acquéreur) — NOM CORRIGÉ
            'slt_categoriePM_Mutation'                => 'nullable|string',
            'txt_formJuridiquePM_Mutation'            => 'nullable|string',
            'txt_denominationPM_Mutation'             => 'nullable|string',
            'txt_nineaPM_Mutation'                    => 'nullable|string',
            'txt_personneRepresentantPM_Mutation'     => 'nullable|string',
            'txt_fonctionPM_Mutation'                 => 'nullable|string',
            'txt_telephonePM_Mutation'                => 'nullable|string',
            'txt_emailPM_Mutation'                    => 'nullable|email',
            'txt_adressePM_Mutation'                  => 'nullable|string',

            // État (acquéreur) — NOM CORRIGÉ
            'slt_etablissementPA_Mutation'            => 'nullable|string',
            'txt_personneResponsablePA_Mutation'      => 'nullable|string',
            'txt_fonctionResponsablePA_Mutation'      => 'nullable|string',
            'txt_telephonePA_Mutation'                => 'nullable|string',
            'txt_emailPA_Mutation'                    => 'nullable|email',
            'txt_ministereTutelePA_Mutation'          => 'nullable|string',

            // Fichier PDF
            'fichierPDFMutation' => 'nullable|file|mimes:pdf|max:122880',
        ], [
            'slt_titulaireMT.required' => '❌ Entrer les informations du Titulaire',
            'slt_titulaireMutation.required' => '❌ Entrer les informations de l\'Acquéreur',
            'txt_typeActeMutation.required' => '❌ Entrer le type d\'acte',
            'dt_dateMutation.required' => '❌ Entrer la date de la mutation',
            'txt_emailPM_MT.email' => '❌ Entrer une adresse email valide',
            'txt_emailPA_MT.email' => '❌ Entrer une adresse email valide',
            'txt_emailPM_Mutation.email' => '❌ Entrer une adresse email valide',
            'txt_emailPA_Mutation.email' => '❌ Entrer une adresse email valide',
            'fichierPDFMutation.file' => 'Le fichier sélectionné est invalide.',
            'fichierPDFMutation.mimes' => 'Le fichier doit être au format PDF.',
            'fichierPDFMutation.max' => 'La taille du fichier ne doit pas dépasser 120 Mo.',
        ]);

        // ============================================================
        // TRAITEMENT TERRAIN + DOSSIER
        // ============================================================
        $terrain = Terrain::with('dossier')
            ->where('txt_num_lotissement', $request->txt_num_lotissementMutation)
            ->where('txt_lotissement', $request->txt_lotissementMutation)
            ->first();

        if (!$terrain || !$terrain->dossier) {
            return back()->withErrors(['terrain' => 'Terrain ou dossier introuvable']);
        }

        $numeroMutation = $terrain->dossier->txt_num_dossier;
        $nicadMutation  = $terrain->txt_nicad;

        // ============================================================
        // TRAITEMENT FICHIER PDF  ⚡ NOUVEAU
        // ============================================================
        $fichierPDF = null;
        if ($request->hasFile('fichierPDFMutation') && $request->file('fichierPDFMutation')->isValid()) {
            $fichierPDF = $request->file('fichierPDFMutation')->store('mutations', 'public');
        }

        // ============================================================
        // CRÉATION MUTATION
        // ============================================================
        Mutation::create([
            'txt_numeroMutation' => $numeroMutation,
            'txt_num_dossier'    => $numeroMutation,
            'txt_nicadMutation'  => $nicadMutation,
            'terrain_id'         => $terrain->id,
            'dossier_id'         => $terrain->txt_num_dossier,

            'txt_num_lotissementMutation' => $validatedData['txt_num_lotissementMutation'],
            'txt_lotissementMutation'     => $validatedData['txt_lotissementMutation'],

            // Titulaire
            'slt_titulaireMT'             => $validatedData['slt_titulaireMT'],
            'txt_nationaliteMT'           => $validatedData['txt_nationaliteMT'] ?? null,
            'slt_civiliteMT'              => $validatedData['slt_civiliteMT'] ?? null,
            'txt_prenomMT'                => $validatedData['txt_prenomMT'] ?? null,
            'txt_nomMT'                   => $validatedData['txt_nomMT'] ?? null,
            'slt_pieceMT'                 => $validatedData['slt_pieceMT'] ?? null,
            'txt_numPieceMT'              => $validatedData['txt_numPieceMT'] ?? null,
            'dt_date_delivranceMT'        => $validatedData['dt_date_delivranceMT'] ?? null,
            'dt_date_naissanceMT'         => $validatedData['dt_date_naissanceMT'] ?? null,
            'txt_lieu_naissanceMT'        => $validatedData['txt_lieu_naissanceMT'] ?? null,
            'txt_adresseMT'               => $validatedData['txt_adresseMT'] ?? null,
            'tel_telephoneMT'             => $validatedData['tel_telephoneMT'] ?? null,
            'txt_representantMT'          => $validatedData['txt_representantMT'] ?? null,
            'tel_telRepresentantMT'       => $validatedData['tel_telRepresentantMT'] ?? null,

            // Personne morale (titulaire)
            'slt_categoriePM_MT'                  => $validatedData['slt_categoriePM_MT'] ?? null,
            'txt_formJuridiquePM_MT'              => $validatedData['txt_formJuridiquePM_MT'] ?? null,
            'txt_denominationPM_MT'               => $validatedData['txt_denominationPM_MT'] ?? null,
            'txt_nineaPM_MT'                      => $validatedData['txt_nineaPM_MT'] ?? null,
            'txt_personneRepresentantPM_MT'       => $validatedData['txt_personneRepresentantPM_MT'] ?? null,
            'txt_fonctionPM_MT'                   => $validatedData['txt_fonctionPM_MT'] ?? null,
            'txt_telephonePM_MT'                  => $validatedData['txt_telephonePM_MT'] ?? null,
            'txt_emailPM_MT'                      => $validatedData['txt_emailPM_MT'] ?? null,
            'txt_adressePM_MT'                    => $validatedData['txt_adressePM_MT'] ?? null,

            // État (titulaire)
            'slt_etablissementPA_MT'              => $validatedData['slt_etablissementPA_MT'] ?? null,
            'txt_personneResponsablePA_MT'        => $validatedData['txt_personneResponsablePA_MT'] ?? null,
            'txt_fonctionResponsablePA_MT'        => $validatedData['txt_fonctionResponsablePA_MT'] ?? null,
            'txt_telephonePA_MT'                  => $validatedData['txt_telephonePA_MT'] ?? null,
            'txt_emailPA_MT'                      => $validatedData['txt_emailPA_MT'] ?? null,
            'txt_ministereTutelePA_MT'            => $validatedData['txt_ministereTutelePA_MT'] ?? null,

            // Acquéreur
            'slt_titulaireMutation'          => $validatedData['slt_titulaireMutation'],
            'txt_prenomMutation'             => $validatedData['txt_prenomMutation'] ?? null,
            'txt_nomMutation'                => $validatedData['txt_nomMutation'] ?? null,
            'txt_nationaliteMutation'        => $validatedData['txt_nationaliteMutation'] ?? null,
            'slt_civiliteMutation'           => $validatedData['slt_civiliteMutation'] ?? null,
            'slt_pieceMutation'              => $validatedData['slt_pieceMutation'] ?? null,
            'txt_numPieceMutation'           => $validatedData['txt_numPieceMutation'] ?? null,
            'dt_date_delivranceMutation'     => $validatedData['dt_date_delivranceMutation'] ?? null,
            'dt_date_naissanceMutation'      => $validatedData['dt_date_naissanceMutation'] ?? null,
            'txt_lieu_naissanceMutation'     => $validatedData['txt_lieu_naissanceMutation'] ?? null,
            'txt_adresseMutation'            => $validatedData['txt_adresseMutation'] ?? null,
            'tel_telephoneMutation'          => $validatedData['tel_telephoneMutation'] ?? null,
            'txt_representantMutation'       => $validatedData['txt_representantMutation'] ?? null,
            'tel_telRepresentantMutation'    => $validatedData['tel_telRepresentantMutation'] ?? null,

            // Acte
            'txt_typeActeMutation'        => $validatedData['txt_typeActeMutation'],
            'txt_nomNotaireMutation'      => $validatedData['txt_nomNotaireMutation'] ?? null,
            'dt_dateMutation'             => $validatedData['dt_dateMutation'],
            'txt_natureMutation'          => $validatedData['txt_natureMutation'] ?? null,
            'txt_jugementMutation'        => $validatedData['txt_jugementMutation'] ?? null,
            'txt_observationMutation'     => $validatedData['txt_observationMutation'] ?? null,

            // Personne morale (acquéreur)
            'slt_categoriePM_Mutation'                => $validatedData['slt_categoriePM_Mutation'] ?? null,
            'txt_formJuridiquePM_Mutation'            => $validatedData['txt_formJuridiquePM_Mutation'] ?? null,
            'txt_denominationPM_Mutation'             => $validatedData['txt_denominationPM_Mutation'] ?? null,
            'txt_nineaPM_Mutation'                    => $validatedData['txt_nineaPM_Mutation'] ?? null,
            'txt_personneRepresentantPM_Mutation'     => $validatedData['txt_personneRepresentantPM_Mutation'] ?? null,
            'txt_fonctionPM_Mutation'                 => $validatedData['txt_fonctionPM_Mutation'] ?? null,
            'txt_telephonePM_Mutation'                => $validatedData['txt_telephonePM_Mutation'] ?? null,
            'txt_emailPM_Mutation'                    => $validatedData['txt_emailPM_Mutation'] ?? null,
            'txt_adressePM_Mutation'                  => $validatedData['txt_adressePM_Mutation'] ?? null,

            // État (acquéreur)
            'slt_etablissementPA_Mutation'            => $validatedData['slt_etablissementPA_Mutation'] ?? null,
            'txt_personneResponsablePA_Mutation'      => $validatedData['txt_personneResponsablePA_Mutation'] ?? null,
            'txt_fonctionResponsablePA_Mutation'      => $validatedData['txt_fonctionResponsablePA_Mutation'] ?? null,
            'txt_telephonePA_Mutation'                => $validatedData['txt_telephonePA_Mutation'] ?? null,
            'txt_emailPA_Mutation'                    => $validatedData['txt_emailPA_Mutation'] ?? null,
            'txt_ministereTutelePA_Mutation'          => $validatedData['txt_ministereTutelePA_Mutation'] ?? null,

            // Fichier PDF (chemin de stockage)
            'fichierPDFMutation' => $fichierPDF,
        ]);

        return redirect()->back()->with('success', '✅ Données enregistrées !');
    }

    public function show(string $id) {}
    public function editMutation(string $id) {}

    // ============================================================
    // UPDATE
    // ============================================================
    public function update(Request $request, $id)
    {
        $mutation = Mutation::findOrFail($id);

        if ($request->has('slt_categoriePR')) {
            $request->merge([
                'slt_categoriePR' => (string) $request->slt_categoriePR,
            ]);
        }

        $validatedData = $request->validate([
            'txt_num_lotissementMutation' => 'required|string',
            'txt_lotissementMutation'     => 'required|string',
            'txt_nicadMutation'           => 'required|string',
            'txt_numeroMutation'          => 'required|string',

            'slt_titulaireMT'             => 'required|string',
            'txt_nationaliteMT'           => 'nullable|string',
            'slt_civiliteMT'              => 'nullable|string',
            'txt_prenomMT'                => 'nullable|string',
            'txt_nomMT'                   => 'nullable|string',
            'slt_pieceMT'                 => 'nullable|string',
            'txt_numPieceMT'              => 'nullable|string',
            'dt_date_delivranceMT'        => 'nullable|date',
            'dt_date_naissanceMT'         => 'nullable|date',
            'txt_lieu_naissanceMT'        => 'nullable|string',
            'txt_adresseMT'               => 'nullable|string',
            'tel_telephoneMT'             => 'nullable|string',
            'txt_representantMT'          => 'nullable|string',
            'tel_telRepresentantMT'       => 'nullable|string',

            // Personne morale (titulaire)
            'slt_categoriePM_MT'                  => 'nullable|string',
            'txt_formJuridiquePM_MT'              => 'nullable|string',
            'txt_denominationPM_MT'               => 'nullable|string',
            'txt_nineaPM_MT'                      => 'nullable|string',
            'txt_personneRepresentantPM_MT'       => 'nullable|string',
            'txt_fonctionPM_MT'                   => 'nullable|string',
            'txt_telephonePM_MT'                  => 'nullable|string',
            'txt_emailPM_MT'                      => 'nullable|email',
            'txt_adressePM_MT'                    => 'nullable|string',

            // État (titulaire)
            'slt_etablissementPA_MT'              => 'nullable|string',
            'txt_personneResponsablePA_MT'        => 'nullable|string',
            'txt_fonctionResponsablePA_MT'        => 'nullable|string',
            'txt_telephonePA_MT'                  => 'nullable|string',
            'txt_emailPA_MT'                      => 'nullable|email',
            'txt_ministereTutelePA_MT'            => 'nullable|string',

            // Acquéreur
            'slt_titulaireMutation'          => 'required|string',
            'txt_prenomMutation'             => 'nullable|string',
            'txt_nomMutation'                => 'nullable|string',
            'txt_nationaliteMutation'        => 'nullable|string',
            'slt_civiliteMutation'           => 'nullable|string',
            'slt_pieceMutation'              => 'nullable|string',
            'txt_numPieceMutation'           => 'nullable|string',
            'dt_date_delivranceMutation'     => 'nullable|date',
            'dt_date_naissanceMutation'      => 'nullable|date',
            'txt_lieu_naissanceMutation'     => 'nullable|string',
            'txt_adresseMutation'            => 'nullable|string',
            'tel_telephoneMutation'          => 'nullable|string',

            // Acte
            'txt_typeActeMutation'        => 'required|string',
            'txt_nomNotaireMutation'      => 'nullable|string',
            'dt_dateMutation'             => 'required|date',
            'txt_natureMutation'          => 'nullable|string',
            'txt_observationMutation'     => 'nullable|string',

            // Personne morale (acquéreur)
            'slt_categoriePM_Mutation'                => 'nullable|string',
            'txt_formJuridiquePM_Mutation'            => 'nullable|string',
            'txt_denominationPM_Mutation'             => 'nullable|string',
            'txt_nineaPM_Mutation'                    => 'nullable|string',
            'txt_personneRepresentantPM_Mutation'     => 'nullable|string',
            'txt_fonctionPM_Mutation'                 => 'nullable|string',
            'txt_telephonePM_Mutation'                => 'nullable|string',
            'txt_emailPM_Mutation'                    => 'nullable|email',
            'txt_adressePM_Mutation'                  => 'nullable|string',

            // État (acquéreur)
            'slt_etablissementPA_Mutation'            => 'nullable|string',
            'txt_personneResponsablePA_Mutation'      => 'nullable|string',
            'txt_fonctionResponsablePA_Mutation'      => 'nullable|string',
            'txt_telephonePA_Mutation'                => 'nullable|string',
            'txt_emailPA_Mutation'                    => 'nullable|email',
            'txt_ministereTutelePA_Mutation'          => 'nullable|string',

            'fichierPDFMutation' => 'nullable|file|mimes:pdf|max:122880',
        ]);

        // Gestion du fichier PDF
        $fichierPDF = $mutation->fichierPDFMutation;
        if ($request->hasFile('fichierPDFMutation') && $request->file('fichierPDFMutation')->isValid()) {
            if ($mutation->fichierPDFMutation) {
                Storage::disk('public')->delete($mutation->fichierPDFMutation);
            }
            $fichierPDF = $request->file('fichierPDFMutation')->store('mutations', 'public');
        }

        $mutation->update([
            'txt_nicadMutation'           => $validatedData['txt_nicadMutation'],
            'txt_numeroMutation'          => $validatedData['txt_numeroMutation'],
            'txt_num_lotissementMutation' => $validatedData['txt_num_lotissementMutation'],
            'txt_lotissementMutation'     => $validatedData['txt_lotissementMutation'],

            'slt_titulaireMT'             => $validatedData['slt_titulaireMT'],
            'txt_nationaliteMT'           => $validatedData['txt_nationaliteMT'] ?? null,
            'slt_civiliteMT'              => $validatedData['slt_civiliteMT'] ?? null,
            'txt_prenomMT'                => $validatedData['txt_prenomMT'] ?? null,
            'txt_nomMT'                   => $validatedData['txt_nomMT'] ?? null,
            'slt_pieceMT'                 => $validatedData['slt_pieceMT'] ?? null,
            'txt_numPieceMT'              => $validatedData['txt_numPieceMT'] ?? null,
            'dt_date_delivranceMT'        => $validatedData['dt_date_delivranceMT'] ?? null,
            'dt_date_naissanceMT'         => $validatedData['dt_date_naissanceMT'] ?? null,
            'txt_lieu_naissanceMT'        => $validatedData['txt_lieu_naissanceMT'] ?? null,
            'txt_adresseMT'               => $validatedData['txt_adresseMT'] ?? null,
            'tel_telephoneMT'             => $validatedData['tel_telephoneMT'] ?? null,
            'txt_representantMT'          => $validatedData['txt_representantMT'] ?? null,
            'tel_telRepresentantMT'       => $validatedData['tel_telRepresentantMT'] ?? null,

            'slt_categoriePM_MT'                  => $validatedData['slt_categoriePM_MT'] ?? null,
            'txt_formJuridiquePM_MT'              => $validatedData['txt_formJuridiquePM_MT'] ?? null,
            'txt_denominationPM_MT'               => $validatedData['txt_denominationPM_MT'] ?? null,
            'txt_nineaPM_MT'                      => $validatedData['txt_nineaPM_MT'] ?? null,
            'txt_personneRepresentantPM_MT'       => $validatedData['txt_personneRepresentantPM_MT'] ?? null,
            'txt_fonctionPM_MT'                   => $validatedData['txt_fonctionPM_MT'] ?? null,
            'txt_telephonePM_MT'                  => $validatedData['txt_telephonePM_MT'] ?? null,
            'txt_emailPM_MT'                      => $validatedData['txt_emailPM_MT'] ?? null,
            'txt_adressePM_MT'                    => $validatedData['txt_adressePM_MT'] ?? null,

            'slt_etablissementPA_MT'              => $validatedData['slt_etablissementPA_MT'] ?? null,
            'txt_personneResponsablePA_MT'        => $validatedData['txt_personneResponsablePA_MT'] ?? null,
            'txt_fonctionResponsablePA_MT'        => $validatedData['txt_fonctionResponsablePA_MT'] ?? null,
            'txt_telephonePA_MT'                  => $validatedData['txt_telephonePA_MT'] ?? null,
            'txt_emailPA_MT'                      => $validatedData['txt_emailPA_MT'] ?? null,
            'txt_ministereTutelePA_MT'            => $validatedData['txt_ministereTutelePA_MT'] ?? null,

            'slt_titulaireMutation'          => $validatedData['slt_titulaireMutation'],
            'txt_prenomMutation'             => $validatedData['txt_prenomMutation'] ?? null,
            'txt_nomMutation'                => $validatedData['txt_nomMutation'] ?? null,
            'txt_nationaliteMutation'        => $validatedData['txt_nationaliteMutation'] ?? null,
            'slt_civiliteMutation'           => $validatedData['slt_civiliteMutation'] ?? null,
            'slt_pieceMutation'              => $validatedData['slt_pieceMutation'] ?? null,
            'txt_numPieceMutation'           => $validatedData['txt_numPieceMutation'] ?? null,
            'dt_date_delivranceMutation'     => $validatedData['dt_date_delivranceMutation'] ?? null,
            'dt_date_naissanceMutation'      => $validatedData['dt_date_naissanceMutation'] ?? null,
            'txt_lieu_naissanceMutation'     => $validatedData['txt_lieu_naissanceMutation'] ?? null,
            'txt_adresseMutation'            => $validatedData['txt_adresseMutation'] ?? null,
            'tel_telephoneMutation'          => $validatedData['tel_telephoneMutation'] ?? null,

            'txt_typeActeMutation'        => $validatedData['txt_typeActeMutation'],
            'txt_nomNotaireMutation'      => $validatedData['txt_nomNotaireMutation'] ?? null,
            'dt_dateMutation'             => $validatedData['dt_dateMutation'],
            'txt_natureMutation'          => $validatedData['txt_natureMutation'] ?? null,
            'txt_observationMutation'     => $validatedData['txt_observationMutation'] ?? null,

            'slt_categoriePM_Mutation'                => $validatedData['slt_categoriePM_Mutation'] ?? null,
            'txt_formJuridiquePM_Mutation'            => $validatedData['txt_formJuridiquePM_Mutation'] ?? null,
            'txt_denominationPM_Mutation'             => $validatedData['txt_denominationPM_Mutation'] ?? null,
            'txt_nineaPM_Mutation'                    => $validatedData['txt_nineaPM_Mutation'] ?? null,
            'txt_personneRepresentantPM_Mutation'     => $validatedData['txt_personneRepresentantPM_Mutation'] ?? null,
            'txt_fonctionPM_Mutation'                 => $validatedData['txt_fonctionPM_Mutation'] ?? null,
            'txt_telephonePM_Mutation'                => $validatedData['txt_telephonePM_Mutation'] ?? null,
            'txt_emailPM_Mutation'                    => $validatedData['txt_emailPM_Mutation'] ?? null,
            'txt_adressePM_Mutation'                  => $validatedData['txt_adressePM_Mutation'] ?? null,

            'slt_etablissementPA_Mutation'            => $validatedData['slt_etablissementPA_Mutation'] ?? null,
            'txt_personneResponsablePA_Mutation'      => $validatedData['txt_personneResponsablePA_Mutation'] ?? null,
            'txt_fonctionResponsablePA_Mutation'      => $validatedData['txt_fonctionResponsablePA_Mutation'] ?? null,
            'txt_telephonePA_Mutation'                => $validatedData['txt_telephonePA_Mutation'] ?? null,
            'txt_emailPA_Mutation'                    => $validatedData['txt_emailPA_Mutation'] ?? null,
            'txt_ministereTutelePA_Mutation'          => $validatedData['txt_ministereTutelePA_Mutation'] ?? null,

            'fichierPDFMutation' => $fichierPDF,
        ]);

        return redirect()->route('parametre.mutation')
            ->with('success', '✅ Mutation mise à jour pour le terrain ' . $mutation->txt_num_lotissementMutation . ' du lotissement ' . $mutation->txt_lotissementMutation . ' !');
    }

    public function destroyMutation(string $id) {}
}