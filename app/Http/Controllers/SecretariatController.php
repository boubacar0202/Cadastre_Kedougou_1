<?php

namespace App\Http\Controllers;
 
use App\Models\Region;
use App\Models\Departement;
use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Dossier;
use App\Models\EvaluationAmenagement;
use App\Models\EvaluationBati;
use App\Models\EvaluationCloture;
use App\Models\EvaluationCoursAmenagee;
use App\Models\Lotissement;
use App\Models\ReferenceCadastrale;
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use App\Models\Titulaire;
use Illuminate\Http\Request;
use Inertia\Inertia; 
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Validation\ValidationException; 
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Mutation;
use Illuminate\Support\Facades\Log;

class SecretariatController extends Controller
{

    //
    public function index()
    {
        $terrains = Terrain::with(['region', 'departement', 'arrondissement', 'commune', 'dossier'])->get();
        
        return Inertia::render("secretariat/index",  [
            "terrains" => $terrains,
        ]);
    }

    public function create()
    {
        $regions = Region::all(); 
        $communes = Commune::select('id', 'slt_commune', 'txt_codeCommune')->get();

        return Inertia::render("secretariat/create",  [
            "regions" => $regions,
            "departements" => [], 
            'communes' => $communes,
  
        ]);
    }
 
    // edit terrain
    public function edit($id)
    {
        $terrain = Terrain::with([
            'region',
            'departement',
            'commune',
            'arrondissement',
            'dossier',
            'titulaire',
            'references_cadastrales',
            'references_usages',
            'evaluations_terrains',
            'evaluations_clotures', 
            'evaluations_amenagements', 
            'evaluations_batis', 
            'evaluations_cours_amenagees'
        ])->findOrFail($id);
 
        return Inertia::render('secretariat/edit', [
            'terrain' => $terrain,  
            'regions' => Region::select('id', 'slt_region')->get(),
            'departements' => Departement::select('id', 'slt_departement')->get(),
            'arrondissements' => Arrondissement::select('id', 'slt_arrondissement')->get(),
            'communes' => Commune::select('id', 'slt_commune', 'txt_codeCommune')->get(), 
            'lotissements' => Lotissement::select('id', 'txt_denomination')->get(),        ]);
    }
  

    public function verifyDossier(Request $request)
    {
        $request->validate([
            'slt_region' => 'nullable|exists:regions,id',
            'slt_departement' => 'nullable|exists:departements,id',
            'slt_arrondissement' => 'nullable|exists:arrondissements,id',
            'slt_commune' => 'nullable|exists:communes,id',
            'txt_num_lotissement' => 'required|string',
            'txt_lotissement' => 'required|string',
        ]);

        // 🔍 RECHERCHE DU TERRAIN PAR LOCALISATION
        $query = Terrain::with(['dossier', 'region', 'departement', 'arrondissement', 'commune']);

        if ($request->filled('slt_region')) {
            $query->where('slt_region', $request->slt_region);
        }
        if ($request->filled('slt_departement')) {
            $query->where('slt_departement', $request->slt_departement);
        }
        if ($request->filled('slt_arrondissement')) {
            $query->where('slt_arrondissement', $request->slt_arrondissement);
        }
        if ($request->filled('slt_commune')) {
            $query->where('slt_commune', $request->slt_commune);
        }

        $query->where('txt_num_lotissement', trim($request->txt_num_lotissement))
            ->where('txt_lotissement', trim($request->txt_lotissement));

        $terrain = $query->first();

        // ❌ TERRAIN NON TROUVÉ
        if (!$terrain) {
            return response()->json([
                'exists' => false,
                'terrain_exists' => false,
                'has_dossier' => false,
                'message' => '❌ Terrain introuvable. Veuillez enregistrer le terrain avant la mutation.',
                'action' => 'create_terrain'
            ], 404);
        }

        // ✅ TERRAIN TROUVÉ
        $response = [
            'exists' => true,
            'terrain_exists' => true,
            'terrain' => $terrain,
            'terrain_info' => [
                'id' => $terrain->id,
                'num_lotissement' => $terrain->txt_num_lotissement,
                'lotissement' => $terrain->txt_lotissement,
                'nicad' => $terrain->txt_nicad ?? 'Non renseigné',
                'num_parcelle' => $terrain->txt_num_parcelle ?? 'Non renseigné',
                'surface' => $terrain->nbr_surface ?? 'Non renseignée',
                'region' => $terrain->region ? $terrain->region->nom : null,
                'departement' => $terrain->departement ? $terrain->departement->nom : null,
                'arrondissement' => $terrain->arrondissement ? $terrain->arrondissement->nom : null,
                'commune' => $terrain->commune ? $terrain->commune->nom : null,
            ]
        ];

        // 📋 VÉRIFICATION DU DOSSIER
        if ($terrain->dossier) {
            $response['has_dossier'] = true;
            $response['dossier'] = $terrain->dossier;
            $response['dossier_info'] = [
                'id' => $terrain->dossier->id,
                'num_dossier' => $terrain->dossier->txt_num_dossier,
                'nicad' => $terrain->dossier->txt_nicad ?? 'Non renseigné',
                'proprietaire' => $terrain->dossier->txt_proprietaire ?? 'Non renseigné',
                'telephone' => $terrain->dossier->txt_telephone ?? 'Non renseigné',
                'email' => $terrain->dossier->txt_email ?? 'Non renseigné',
                'date_creation' => $terrain->dossier->created_at ? $terrain->dossier->created_at->format('d/m/Y H:i') : 'Non renseignée',
                'statut' => $terrain->dossier->statut ?? 'Actif',
            ];
            
            $response['message'] = "✅ Terrain existant avec dossier N° {$terrain->dossier->txt_num_dossier}";
            $response['action'] = 'dossier_exists';
            
        } else {
            // 📝 TERRAIN SANS DOSSIER
            $response['has_dossier'] = false;
            $response['message'] = "✅ Terrain existant sans dossier. Prêt à créer un nouveau dossier.";
            $response['action'] = 'create_dossier';
        }

        return response()->json($response, 200);
    }

    // Récupérer les références de lotissement
    public function fetchReferencesLotissement(): JsonResponse
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

    public function dernierOrdre($annee)
    {
        $dernier = Dossier::whereYear('dt_date_creation', $annee)
            ->max('txt_num_dordre');

        return response()->json([
            'dernier' => $dernier ? (int) $dernier : 0
        ]);
    }

    // store
    public function store(Request $request)
    {
        // 🔥 Nettoyage NICAD
        $nicadNettoye = preg_replace('/\s+/', '', $request->txt_nicad ?? '');

        // Récupérer l'ID du dossier en cours (si édition)
        $dossierId = $request->route('dossier') // selon votre routing
                    ?? $request->input('dossier_id') // ou via champ caché
                    ?? null;

        // ⚡ VÉRIFICATION DU NICAD DANS LA TABLE TERRAINS
        $query = Terrain::whereRaw("
            REPLACE(REPLACE(REPLACE(txt_nicad,' ',''),'\n',''),'\r','') = ?
        ", [$nicadNettoye]);

        // Si on est en édition, on exclut le terrain du dossier courant
        if ($dossierId) {
            $query->where(function ($q) use ($dossierId) {
                $q->whereNull('dossier_id')      // terrain sans dossier
                ->orWhere('dossier_id', '!=', $dossierId); // ou terrain d'un autre dossier
            });
        }

        $terrainExistant = $query->first();

        // 🚨 Si le NICAD existe déjà dans un terrain (hors dossier courant)
        if ($terrainExistant) {
            if ($terrainExistant->dossier) {
                // Le terrain a un dossier (forcément différent puisque exclu)
                throw ValidationException::withMessages([
                    'txt_nicad' => 'Ce NICAD existe et est déjà associé au dossier N° '.
                        ($terrainExistant->dossier->txt_num_dossier ?? 'inconnu')
                ]);
            } else {
                // Terrain sans dossier → réutilisable
                $terrainAReutiliser = $terrainExistant;
            }
        }

        // 🔥 Validation (inchangée)
        $validated = $request->validate([
            'txt_num_dossier' => 'required|string|unique:dossiers,txt_num_dossier',
            'slt_service_rendu' => 'nullable|string',
            'txt_etat_cession' => 'nullable|string',
            'txt_cession_definitive' => 'nullable|string',
            'dt_date_creation' => 'required|date',

            'rd_immatriculation_terrain' => 'required|string',
            'slt_dependant_domaine' => 'nullable|string',
            'ussu_bornage' => 'nullable|string',
            'slt_lf' => 'nullable|string',
            'txt_num_requisition' => 'nullable|string',
            'txt_surface_bornage' => 'nullable|string',
            'dt_date_bornage' => 'nullable|date',
            'txt_nom_geometre' => 'nullable|string',

            'slt_titulaire' => 'required|string',
            'txt_nationalite' => 'nullable|string',
            'slt_civilite' => 'nullable|string',
            'txt_prenom' => 'nullable|string',
            'txt_nom' => 'nullable|string',
            'slt_piece' => 'nullable|string',
            'txt_numPiece' => 'nullable|string',
            'dt_date_delivrance' => 'nullable|date|after:dt_date_naissance',
            'dt_date_naissance' => 'nullable|date',
            'txt_lieu_naissance' => 'nullable|string',
            'txt_adresse' => 'nullable|string',
            'tel_telephone' => 'nullable|string',
            'eml_email' => 'nullable|string',
            'txt_representant' => 'nullable|string',
            'tel_telRepresentant' => 'nullable|string',

            'slt_categoriePM' => 'nullable|string',
            'txt_formJuridiquePM' => 'nullable|string',
            'txt_denominationPM' => 'nullable|string',
            'txt_nineaPM' => 'nullable|string',
            'txt_perssonneRepresentantPM' => 'nullable|string',
            'txt_fonctionPM' => 'nullable|string',
            'txt_telephonePM' => 'nullable|string',
            'txt_emailPM' => 'nullable|string',
            'txt_adressePM' => 'nullable|string',

            'slt_etablissementPA' => 'nullable|string',
            'txt_personneResponsablePA' => 'nullable|string',
            'txt_fonctionResponsablePA' => 'nullable|string',
            'txt_telephonePA' => 'nullable|string',
            'txt_emailPA' => 'nullable|string',
            'txt_ministèreTutelePA' => 'nullable|string',

            'fichierPDF' => 'nullable|file|mimes:pdf|max:120400',

            'txt_lotissement' => 'nullable|string',
            'txt_HorsLotissement' => 'nullable|string',
            'txt_num_lotissement' => 'nullable|string',
            'txt_num_section' => 'nullable|string',
            'txt_num_parcelle' => 'nullable|string',
            'nbr_surface' => 'nullable|numeric',
            'txt_num_titre' => 'nullable|string',
            'txt_titre_mere' => 'nullable|string',
            'txt_appartement' => 'nullable|string',
            'slt_document_admin' => 'nullable|string',
            'txt_num_deliberation' => 'nullable|string',
            'dt_date_deliberation' => 'nullable|date',
            'txt_nicad' => 'nullable|string',
            'slt_statut_nicad' => 'nullable|string',
            'slt_motif_annulation_nicad' => 'nullable|string',

            'slt_region' => 'required|exists:regions,id',
            'slt_departement' => 'required|exists:departements,id',
            'slt_arrondissement' => 'required|exists:arrondissements,id',
            'slt_commune' => 'required|exists:communes,id',
        ],
            [
                'txt_num_dossier.required' => 'Le champ "Numéro de dossier" est obligatoire.',
                'slt_region.required' => 'Le champ "Nombre de parcelles" est obligatoire.',
                'slt_departement.required' => 'Le champ "Departement" est obligatoire.',
                'slt_arrondissement.required' => 'Le champ "Arrondissement" est obligatoire.',
                'slt_commune.required' => 'Le champ "Commune" est obligatoire.',
                'txt_surfaceLotissement.required' => 'Le champ "Surface" est obligatoire.',
                'txt_num_dossier.unique' => 'Le numéro de dossier est deja pris.',
                'slt_titulaire.required' => 'Le champ "Titulaire" est obligatoire.',
                'rd_immatriculation_terrain.required' => 'Le champ "Immatriculation terrain" est obligatoire.',
            ]);

        // 📁 fichier PDF
        if ($request->hasFile('fichierPDF')) {
            $validated['fichierPDF'] = $request->file('fichierPDF')
                ->store('numerisation', 'public');
        }
        
        // Après validation, vérification du couple
        $lotissementNettoye = trim(preg_replace('/\s+/', ' ', $request->txt_lotissement ?? ''));
        $numLotissementNettoye = trim(preg_replace('/\s+/', ' ', $request->txt_num_lotissement ?? ''));

        // Vérification du couple UNIQUEMENT si les deux champs sont remplis
        if (!empty($lotissementNettoye) && !empty($numLotissementNettoye)) {
            $terrainExistantCouple = Terrain::where('txt_lotissement', $lotissementNettoye)
                ->where('txt_num_lotissement', $numLotissementNettoye)
                ->first();

            if ($terrainExistantCouple) {
                // Vérification si le terrain a un dossier associé
                if ($terrainExistantCouple->dossier()->exists()) {
                    $dossier = $terrainExistantCouple->dossier;
                    $titulaire = $terrainExistantCouple->titulaire;
                    
                    $message = 'Ce terrain existe déjà.';
                    
                    if ($dossier) {
                        $message .= ' Dossier N° ' . $dossier->txt_num_dossier . '.';
                    }
                    if ($titulaire) {
                        $nomComplet = trim(($titulaire->txt_prenom ?? '') . ' ' . ($titulaire->txt_nom ?? ''));
                        if (!empty($nomComplet)) {
                            $message .= ' Propriétaire : ' . $nomComplet;
                        }
                    }
                    
                    throw ValidationException::withMessages([
                        'txt_lotissement' => $message,
                    ]);
                } else {
                    // Terrain sans dossier, on peut le réutiliser
                    $terrainAReutiliser = $terrainExistantCouple;
                }
            } else {
                // Aucun terrain existant
                $terrainAReutiliser = null;
            }
        } else {
            // Un ou les deux champs sont vides, on passe sans vérification
            $terrainAReutiliser = null;
        }

        return DB::transaction(function () use ($validated) {

            // 🔢 numéro ordre optimisé
            $numeroDordre = (Dossier::max('txt_num_dordre') ?? 0) + 1;

            // 📁 DOSSIER
            $dossier = Dossier::create([
                'txt_num_dossier' => $validated['txt_num_dossier'],
                'txt_num_dordre' => $numeroDordre,
                'slt_service_rendu' => $validated['slt_service_rendu'] ?? null,
                'txt_etat_cession' => $validated['txt_etat_cession'] ?? null,
                'txt_cession_definitive' => $validated['txt_cession_definitive'] ?? null,
                'dt_date_creation' => $validated['dt_date_creation'],
            ]);

            // 📌 REFERENCE CADASTRALE
            $reference = ReferenceCadastrale::create([
                'rd_immatriculation_terrain' => $validated['rd_immatriculation_terrain'],
                'slt_dependant_domaine' => $validated['slt_dependant_domaine'] ?? null,
                'ussu_bornage' => $validated['ussu_bornage'] ?? null,
                'slt_lf' => $validated['slt_lf'] ?? null,
                'txt_num_requisition' => $validated['txt_num_requisition'] ?? null,
                'txt_surface_bornage' => $validated['txt_surface_bornage'] ?? null,
                'dt_date_bornage' => $validated['dt_date_bornage'] ?? null,
                'txt_nom_geometre' => $validated['txt_nom_geometre'] ?? null,
            ]);

            // 👤 TITULAIRE
            $titulaire = Titulaire::create([
                'slt_titulaire' => $validated['slt_titulaire'],
                'txt_nationalite' => $validated['txt_nationalite'] ?? null,
                'slt_civilite' => $validated['slt_civilite'] ?? null,
                'txt_prenom' => $validated['txt_prenom'] ?? null,
                'txt_nom' => $validated['txt_nom'] ?? null,
                'slt_piece' => $validated['slt_piece'] ?? null,
                'txt_numPiece' => $validated['txt_numPiece'] ?? null,
                'dt_date_delivrance' => $validated['dt_date_delivrance'] ?? null,
                'dt_date_naissance' => $validated['dt_date_naissance'] ?? null,
                'txt_lieu_naissance' => $validated['txt_lieu_naissance'] ?? null,
                'txt_adresse' => $validated['txt_adresse'] ?? null,
                'tel_telephone' => $validated['tel_telephone'] ?? null,
                'eml_email' => $validated['eml_email'] ?? null,
                'txt_representant' => $validated['txt_representant'] ?? null,
                'tel_telRepresentant' => $validated['tel_telRepresentant'] ?? null,

                'slt_categoriePM' => $validated['slt_categoriePM'] ?? null,
                'txt_formJuridiquePM' => $validated['txt_formJuridiquePM'] ?? null,
                'txt_denominationPM' => $validated['txt_denominationPM'] ?? null,
                'txt_nineaPM' => $validated['txt_nineaPM'] ?? null,
                'txt_perssonneRepresentantPM' => $validated['txt_perssonneRepresentantPM'] ?? null,
                'txt_fonctionPM' => $validated['txt_fonctionPM'] ?? null,
                'txt_telephonePM' => $validated['txt_telephonePM'] ?? null,
                'txt_emailPM' => $validated['txt_emailPM'] ?? null,
                'txt_adressePM' => $validated['txt_adressePM'] ?? null,

                'slt_etablissementPA' => $validated['slt_etablissementPA'] ?? null,
                'txt_personneResponsablePA' => $validated['txt_personneResponsablePA'] ?? null,
                'txt_fonctionResponsablePA' => $validated['txt_fonctionResponsablePA'] ?? null,
                'txt_telephonePA' => $validated['txt_telephonePA'] ?? null,
                'txt_emailPA' => $validated['txt_emailPA'] ?? null,
                'txt_ministèreTutelePA' => $validated['txt_ministèreTutelePA'] ?? null,

                'fichierPDF' => $validated['fichierPDF'] ?? null,
            ]);

            // 🏡 TERRAIN
            $terrain = Terrain::create([
                'txt_lotissement' => $validated['txt_lotissement'] ?? null,
                'txt_HorsLotissement' => $validated['txt_HorsLotissement'] ?? null,
                'txt_num_lotissement' => $validated['txt_num_lotissement'] ?? null,
                'txt_num_section' => $validated['txt_num_section'] ?? null,
                'txt_num_parcelle' => $validated['txt_num_parcelle'] ?? null,
                'nbr_surface' => $validated['nbr_surface'] ?? null,
                'txt_num_titre' => $validated['txt_num_titre'] ?? null,
                'txt_titre_mere' => $validated['txt_titre_mere'] ?? null,
                'txt_appartement' => $validated['txt_appartement'] ?? null,
                'slt_document_admin' => $validated['slt_document_admin'] ?? null,
                'txt_num_deliberation' => $validated['txt_num_deliberation'] ?? null,
                'dt_date_deliberation' => $validated['dt_date_deliberation'] ?? null,
                'txt_nicad' => $validated['txt_nicad'] ?? null,
                'slt_statut_nicad' => $validated['slt_statut_nicad'] ?? null,
                'slt_motif_annulation_nicad' => $validated['slt_motif_annulation_nicad'] ?? null,

                'slt_region' => $validated['slt_region'],
                'slt_departement' => $validated['slt_departement'],
                'slt_arrondissement' => $validated['slt_arrondissement'],
                'slt_commune' => $validated['slt_commune'],

                'txt_num_dossier' => $dossier->id,
                'reference_cadastrale_id' => $reference->id,
                'titulaire_id' => $titulaire->id,
            ]);

            return redirect()->route('secretariat.create')->with([
                'nbr_surface' => $terrain->nbr_surface,
                'txt_nicad' => $terrain->txt_nicad,
                'txt_num_dossier' => $dossier->txt_num_dossier,
            ]);
        });
    }


    // update Upload PDF
    public function uploadPDF(Request $request, Terrain $terrain)
    {
        $request->validate([
            'fichierPDF' => 'required|file|mimes:pdf|max:20480', // 10 Mo max
        ]);

        // Supprimer ancien fichier si existant
        if ($terrain->titulaire->fichierPDF) {
            Storage::disk('public')->delete($terrain->titulaire->fichierPDF);
        }

        $path = $request->file('fichierPDF')->store('numerisation', 'public');

        $terrain->titulaire->update(['fichierPDF' => $path]);

        return response()->json(['success' => true, 'path' => $path]);
    }
 
    
    // Mise à jour d'un terrain
    public function update(Request $request, $id)
    {
         
        // dd($request->all()); 
        $terrain = Terrain::findOrFail($id); 

        // ✅ 1️⃣ Vérifier le doublon en ignorant les accents et la casse
        $existingTerrain = Terrain::whereRaw('LOWER(txt_nicad) = LOWER(?)', [$request->txt_nicad])
            ->where('id', '!=', $id)
            ->with('dossier')
            ->first();

        if ($existingTerrain) {
            $message = "Le NICAD {$request->txt_nicad} est déjà attribué au dossier {$existingTerrain->dossier->txt_num_dossier}.";
            return back()->withErrors(['txt_nicad' => $message])->withInput();
        }
 

        // ✅ 2️⃣ Validation
        $request->validate([
            'txt_nicad' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('terrains', 'txt_nicad')->ignore($id, 'id'),
            ],
            // ... autres validations
        ]);
        
        // ✅ 3️⃣ Validation conditionnelle pour les champs Mutation
        $request->validate([
            'slt_titulaireMutation' => 'nullable|string',

            'txt_typeActeMutation' => [
                Rule::requiredIf(function () use ($request) {
                    $val = $request->input('slt_titulaireMutation');
                    return !is_null($val) && $val !== '' 
                        && $val !== 'null' && $val !== 'undefined';
                }),
                'nullable',
                'string',
                'max:255',
            ],

            'dt_dateMutation' => [
                Rule::requiredIf(function () use ($request) {
                    $val = $request->input('slt_titulaireMutation');
                    return !is_null($val) && $val !== '' 
                        && $val !== 'null' && $val !== 'undefined';
                }),
                'nullable',
                'date',
            ],
        ], [
            'txt_typeActeMutation.required' => 'Le type d\'acte est obligatoire pour une mutation.',
            'dt_dateMutation.required'      => 'La date de la mutation est obligatoire.',
        ]);
 
        // ✅ Mise à jour du terrain (champs directs)
        $terrain->update($request->only([
            'txt_nicad',
            'slt_statut_nicad',
            'slt_region',
            'slt_departement',
            'slt_commune',
            'slt_arrondissement',
            'txt_lotissement',
            'txt_HorsLotissement',
            'txt_num_lotissement',
            'txt_num_section',
            'txt_num_parcelle',
            'txt_titre_mere',
            'txt_num_titre',
            'txt_appartement',
            'nbr_surface',
            'slt_document_admin',
            'txt_num_deliberation',
            'dt_date_deliberation',
            'slt_statut_nicad',
            'slt_motif_annulation_nicad',
        ]));

        // ✅ Mise à jour de la relation Dossier
        if ($terrain->dossier) {
            $terrain->dossier->update($request->only([
                'txt_num_dossier',
                'txt_num_dordre',
                'slt_service_rendu',
                'txt_etat_cession',
                'txt_cession_definitive',
                'dt_date_creation',
            ]));
        }

        // ✅ Mise à jour de la relation Références Cadastrales
        if ($terrain->references_cadastrales) {
            $terrain->references_cadastrales->update($request->only([
                'rd_immatriculation_terrain',
                'slt_dependant_domaine',
                'issu_bornage', 
                'slt_lf',
                'txt_num_requisition',
                'txt_surface_bornage',
                'dt_date_bornage',
                'txt_nom_geometre',
            ]));
        }


        // ✅ Mise à jour de la relation Titulaire 
        if ($terrain->titulaire) {
            $titulaireExistant = $terrain->titulaire;

            // 🎯 On part TOUJOURS des valeurs existantes en BDD
            $titulaireData = [
                'slt_titulaire'                 => $titulaireExistant->slt_titulaire,
                'txt_nationalite'               => $titulaireExistant->txt_nationalite,
                'slt_civilite'                  => $titulaireExistant->slt_civilite,
                'txt_prenom'                    => $titulaireExistant->txt_prenom,
                'txt_nom'                       => $titulaireExistant->txt_nom,
                'slt_piece'                     => $titulaireExistant->slt_piece,
                'txt_numPiece'                  => $titulaireExistant->txt_numPiece,
                'dt_date_delivrance'            => $titulaireExistant->dt_date_delivrance,
                'dt_date_naissance'             => $titulaireExistant->dt_date_naissance,
                'txt_lieu_naissance'            => $titulaireExistant->txt_lieu_naissance,
                'txt_adresse'                   => $titulaireExistant->txt_adresse,
                'tel_telephone'                 => $titulaireExistant->tel_telephone,
                'txt_ninea'                     => $titulaireExistant->txt_ninea,
                'eml_email'                     => $titulaireExistant->eml_email,
                'txt_representant'              => $titulaireExistant->txt_representant,
                'tel_telRepresentant'           => $titulaireExistant->tel_telRepresentant,
                'slt_categoriePM'               => $titulaireExistant->slt_categoriePM,
                'txt_formJuridiquePM'           => $titulaireExistant->txt_formJuridiquePM,
                'txt_denominationPM'            => $titulaireExistant->txt_denominationPM,
                'txt_nineaPM'                   => $titulaireExistant->txt_nineaPM,
                'txt_perssonneRepresentantPM'   => $titulaireExistant->txt_perssonneRepresentantPM,
                'txt_fonctionPM'                => $titulaireExistant->txt_fonctionPM,
                'txt_telephonePM'               => $titulaireExistant->txt_telephonePM,
                'txt_emailPM'                   => $titulaireExistant->txt_emailPM,
                'txt_adressePM'                 => $titulaireExistant->txt_adressePM,
                'slt_etablissementPA'           => $titulaireExistant->slt_etablissementPA,
                'txt_personneResponsablePA'     => $titulaireExistant->txt_personneResponsablePA,
                'txt_fonctionResponsablePA'     => $titulaireExistant->txt_fonctionResponsablePA,
                'txt_telephonePA'               => $titulaireExistant->txt_telephonePA,
                'txt_emailPA'                   => $titulaireExistant->txt_emailPA,
                'txt_ministèreTutelePA'         => $titulaireExistant->txt_ministèreTutelePA,
            ];

            // 🎯 Source : Mutation OU Normal
            $useMutation = $request->filled('slt_titulaireMutation');
            $suffix = $useMutation ? 'Mutation' : '';

            // 🎯 Mapping : [champ BDD => clé request de base]
            $mapping = [
                'slt_titulaire'               => 'slt_titulaire',
                'txt_nationalite'             => 'txt_nationalite',
                'slt_civilite'                => 'slt_civilite',
                'txt_prenom'                  => 'txt_prenom',
                'txt_nom'                     => 'txt_nom',
                'slt_piece'                   => 'slt_piece',
                'txt_numPiece'                => 'txt_numPiece',
                'dt_date_delivrance'          => 'dt_date_delivrance',
                'dt_date_naissance'           => 'dt_date_naissance',
                'txt_lieu_naissance'          => 'txt_lieu_naissance',
                'txt_adresse'                 => 'txt_adresse',
                'tel_telephone'               => 'tel_telephone',
                'txt_ninea'                   => 'txt_ninea',
                'eml_email'                   => 'eml_email',
                'txt_representant'            => 'txt_representant',
                'tel_telRepresentant'         => 'tel_telRepresentant',
                'slt_categoriePM'             => 'slt_categoriePM',
                'txt_formJuridiquePM'         => 'txt_formJuridiquePM',
                'txt_denominationPM'          => 'txt_denominationPM',
                'txt_nineaPM'                 => 'txt_nineaPM',
                'txt_perssonneRepresentantPM' => 'txt_perssonneRepresentantPM',
                'txt_fonctionPM'              => 'txt_fonctionPM',
                'txt_telephonePM'             => 'txt_telephonePM',
                'txt_emailPM'                 => 'txt_emailPM',
                'txt_adressePM'               => 'txt_adressePM',
                'slt_etablissementPA'         => 'slt_etablissementPA',
                'txt_personneResponsablePA'   => 'txt_personneResponsablePA',
                'txt_fonctionResponsablePA'   => 'txt_fonctionResponsablePA',
                'txt_telephonePA'             => 'txt_telephonePA',
                'txt_emailPA'                 => 'txt_emailPA',
                'txt_ministèreTutelePA'       => 'txt_ministèreTutelePA',
            ];

            // 🎯 On écrase uniquement les champs remplis
            foreach ($mapping as $bddField => $baseKey) {
                $requestKey = $baseKey . $suffix;
                if ($request->filled($requestKey)) {
                    $titulaireData[$bddField] = $request->input($requestKey);
                }
            }

            // 🚨 Sécurité : slt_titulaire jamais null
            if (empty($titulaireData['slt_titulaire'])) {
                return back()->withErrors([
                    'slt_titulaire' => "Le champ Titulaire est obligatoire."
                ])->withInput();
            }

            // Gestion PDF titulaire
            if ($request->hasFile('fichierPDF') && $request->file('fichierPDF')->isValid()) {
                if ($terrain->titulaire->fichierPDF) {
                    Storage::disk('public')->delete($terrain->titulaire->fichierPDF);
                }
                $titulaireData['fichierPDF'] = $request->file('fichierPDF')->store('numerisation', 'public');
            } else {
                $titulaireData['fichierPDF'] = $terrain->titulaire->fichierPDF;
            }

            $terrain->titulaire->update($titulaireData);
        }
         
        // ✅ Créer une nouvelle mutation dès que slt_titulaireMutation est rempli 
        if ($request->filled('slt_titulaireMutation')) {

            // 🎯 Mapping : [champ BDD mutation => clé request]
            $mutationData = [
                // Références
                'txt_numeroMutation'          => $terrain->dossier?->txt_num_dossier,
                'txt_nicadMutation'           => $terrain->txt_nicad,
                'txt_num_lotissementMutation' => $terrain->txt_num_lotissement,
                'txt_lotissementMutation'     => $terrain->txt_lotissement,

                // Ancien titulaire (champs normaux, suffixe MT)
                'slt_titulaireMT'             => $request->input('slt_titulaire'),
                'txt_nationaliteMT'           => $request->input('txt_nationalite'),
                'slt_civiliteMT'              => $request->input('slt_civilite'),
                'txt_prenomMT'                => $request->input('txt_prenom'),
                'txt_nomMT'                   => $request->input('txt_nom'),
                'slt_pieceMT'                 => $request->input('slt_piece'),
                'txt_numPieceMT'              => $request->input('txt_numPiece'),
                'dt_date_delivranceMT'        => $request->input('dt_date_delivrance'),
                'dt_date_naissanceMT'         => $request->input('dt_date_naissance'),
                'txt_lieu_naissanceMT'        => $request->input('txt_lieu_naissance'),
                'txt_adresseMT'               => $request->input('txt_adresse'),
                'tel_telephoneMT'             => $request->input('tel_telephone'),
                'txt_representantMT'          => $request->input('txt_representant'),
                'tel_telRepresentantMT'       => $request->input('tel_telRepresentant'),

                // Personne physique (ancien titulaire)
                'slt_categoriePM_MT'          => $request->input('slt_categoriePM'),
                'txt_formJuridiquePM_MT'      => $request->input('txt_formJuridiquePM'),
                'txt_denominationPM_MT'       => $request->input('txt_denominationPM'),
                'txt_nineaPM_MT'              => $request->input('txt_nineaPM'),
                'txt_perssonneRepresentantPM_MT' => $request->input('txt_perssonneRepresentantPM'),
                'txt_fonctionPM_MT'           => $request->input('txt_fonctionPM'),
                'txt_telephonePM_MT'          => $request->input('txt_telephonePM'),
                'txt_emailPM_MT'              => $request->input('txt_emailPM'),
                'txt_adressePM_MT'            => $request->input('txt_adressePM'),

                // Personne morale (ancien titulaire)
                'slt_etablissementPA_MT'      => $request->input('slt_etablissementPA'),
                'txt_personneResponsablePA_MT'=> $request->input('txt_personneResponsablePA'),
                'txt_fonctionResponsablePA_MT'=> $request->input('txt_fonctionResponsablePA'),
                'txt_telephonePA_MT'          => $request->input('txt_telephonePA'),
                'txt_emailPA_MT'              => $request->input('txt_emailPA'),
                'txt_ministèreTutelePA_MT'    => $request->input('txt_ministèreTutelePA'),

                // Acquéreur (suffixe Mutation)
                'slt_titulaireMutation'       => $request->input('slt_titulaireMutation'),
                'txt_prenomMutation'          => $request->input('txt_prenomMutation'),
                'txt_nomMutation'             => $request->input('txt_nomMutation'),
                'txt_nationaliteMutation'     => $request->input('txt_nationaliteMutation'),
                'slt_civiliteMutation'        => $request->input('slt_civiliteMutation'),
                'slt_pieceMutation'           => $request->input('slt_pieceMutation'),
                'txt_numPieceMutation'        => $request->input('txt_numPieceMutation'),
                'dt_date_delivranceMutation'  => $request->input('dt_date_delivranceMutation'),
                'dt_date_naissanceMutation'   => $request->input('dt_date_naissanceMutation'),
                'txt_lieu_naissanceMutation'  => $request->input('txt_lieu_naissanceMutation'),
                'txt_adresseMutation'         => $request->input('txt_adresseMutation'),
                'tel_telephoneMutation'       => $request->input('tel_telephoneMutation'),
                'txt_representantMutation'    => $request->input('txt_representantMutation'),
                'tel_telRepresentantMutation' => $request->input('tel_telRepresentantMutation'),
                'txt_typeActeMutation'        => $request->input('txt_typeActeMutation'),
                'txt_nomNotaireMutation'      => $request->input('txt_nomNotaireMutation'),
                'dt_dateMutation'             => $request->input('dt_dateMutation'),
                'txt_natureMutation'          => $request->input('txt_natureMutation'),
                'txt_observationMutation'     => $request->input('txt_observationMutation'),

                // Personne physique (acquéreur)
                'slt_categoriePM_Mutation'    => $request->input('slt_categoriePM_Mutation'),
                'txt_formJuridiquePM_Mutation'=> $request->input('txt_formJuridiquePM_Mutation'),
                'txt_denominationPM_Mutation' => $request->input('txt_denominationPM_Mutation'),
                'txt_nineaPM_Mutation'        => $request->input('txt_nineaPM_Mutation'),
                'txt_perssonneRepresentantPM_Mutation' => $request->input('txt_perssonneRepresentantPM_Mutation'),
                'txt_fonctionPM_Mutation'     => $request->input('txt_fonctionPM_Mutation'),
                'txt_telephonePM_Mutation'    => $request->input('txt_telephonePM_Mutation'),
                'txt_emailPM_Mutation'        => $request->input('txt_emailPM_Mutation'),
                'txt_adressePM_Mutation'      => $request->input('txt_adressePM_Mutation'),

                // Personne morale (acquéreur)
                'slt_etablissementPA_Mutation'=> $request->input('slt_etablissementPA_Mutation'),
                'txt_personneResponsablePA_Mutation' => $request->input('txt_personneResponsablePA_Mutation'),
                'txt_fonctionResponsablePA_Mutation' => $request->input('txt_fonctionResponsablePA_Mutation'),
                'txt_telephonePA_Mutation'    => $request->input('txt_telephonePA_Mutation'),
                'txt_emailPA_Mutation'        => $request->input('txt_emailPA_Mutation'),
                'txt_ministèreTutelePA_Mutation' => $request->input('txt_ministèreTutelePA_Mutation'),
            ];

            // Gestion PDF mutation
            if ($request->hasFile('fichierPDFMutation') && $request->file('fichierPDFMutation')->isValid()) {
                $mutationData['fichierPDFMutation'] = $request->file('fichierPDFMutation')->store('mutations', 'public');
            } else {
                $mutationData['fichierPDFMutation'] = null;
            }

            // Clé étrangère
            $mutationData['terrain_id'] = $terrain->id;

            \App\Models\Mutation::create($mutationData);
        }


        // ✅ Mise à jour de la relation reference_usage
        if ($terrain->references_usages && $request->has('occupants')) {
            foreach ($request->input('occupants') as $occupantData) {
                ReferenceUsage::updateOrCreate(
                    [
                        'id' => $occupantData['id'] ?? null, // ou autre champ unique
                    ],
                    array_merge([
                        'txt_nicad' => $terrain->txt_nicad,
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier,
                        'slt_usage' => $request->input('slt_usage'),
                        'slt_residence' => $request->input('slt_residence'),
                        'nbr_montantLoyerTotal' => $request->input('nbr_montantLoyerTotal') ?? 0,
                        'nbr_TVATotal' => $request->input('nbr_TVATotal') ?? 0,
                    ], $occupantData)
                );
            } 
            
        }

        // ✅ Mise à jour de la relation evaluation_terrains
        if ($terrain->evaluations_terrains) {
            $terrain->evaluations_terrains->update($request->only([
                'nbr_surface',
                'txt_superficie_bati_sol',
                'txt_superficie_totale',
                'slt_secteur',
                'nbr_prix_metre_carre',
                'nbr_valeur_terrain',
                'nbr_valeurVenaleLimeuble',
                'nbr_valeurLocative',
                'dt_dateEvaluation',
            ]));
        }

        // ✅ Mise à jour de la relation evaluations_batis
        if ($terrain->evaluations_batis && $request->has('occupantsBP')) {
            foreach ($request->input('occupantsBP') as $occupantData) {
                EvaluationBati::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'txt_dependant_domainePR' => $request->input('txt_dependant_domainePR'),
                        'slt_categoriePR' => $request->input('slt_categoriePR'),
                        'nbr_prix_metre_carrePR' => $request->input('nbr_prix_metre_carrePR'),
                        'nbr_surface_bati_solPR' => $request->input('nbr_surface_bati_solPR'),
                        'nbr_niveauPR' => $request->input('nbr_niveauPR'),
                        'nbr_surface_utilePR' => $request->input('nbr_surface_utilePR'),
                        'slt_coeffPR' => $request->input('slt_coeffPR'),
                        'nbr_surface_corrigerPR' => $request->input('nbr_surface_corrigerPR'),
                        'nbr_valeurPR' => $request->input('nbr_valeurPR'),
                        'currentCat' => $request->input('currentCat'),
                        'txt_valeur_terrain_bati' => $request->input('txt_valeur_terrain_bati') ?? 0,
                    ], $occupantData)
                );

            }
        }

        // ✅ Mise à jour de la relation evaluation_cours_amenagees
        if ($terrain->evaluations_cours_amenagees && $request->has('occupantsCA')) {
            foreach ($request->input('occupantsCA') as $occupantData) {
                EvaluationCoursAmenagee::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'nbr_valeur_total_ca' => $request->input('nbr_valeur_total_ca') ?? 0,
                    ], $occupantData)
                );

            }
        }

        // ✅ Mise à jour de la relation evaluation_clotures
        if ($terrain->evaluations_clotures && $request->has('occupantsCL')) {
            foreach ($request->input('occupantsCL') as $occupantData) {
                EvaluationCloture::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'nbr_valeur_total_clotur' => $request->input('nbr_valeur_total_clotur') ?? 0,
                    ], $occupantData)
                );

            }
        }
        $txtNicad = $terrain->txt_nicad;
        // ✅ Mise à jour de la relation evaluation_amenagements
        if ($terrain->evaluations_amenagements && $request->has('occupantsAP')) {
            foreach ($request->input('occupantsAP') as $occupantData) {
                EvaluationAmenagement::updateOrCreate(
                    [
                        'txt_nicad' => $txtNicad ?? $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'nbr_valeur_totale_ap' => $request->input('nbr_valeur_totale_ap') ?? 0,
                    ], $occupantData)
                ); 

            }
        }


        return redirect()->route('donnee.create')
            ->with('success', 'Terrain '. $terrain->txt_nicad. ' mis à jour avec succès');     
 
       
    }

}