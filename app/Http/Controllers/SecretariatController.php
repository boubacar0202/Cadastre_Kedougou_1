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
use App\Models\ReferenceCadastrale;
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use App\Models\Titulaire;
use Illuminate\Http\Request;
use Inertia\Inertia;
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

        return Inertia::render("secretariat/create",  [
            "regions" => $regions,
            "departements" => [], 
 
  
        ]);
    }
 
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
            'communes' => Commune::select('id', 'slt_commune')->get(),  
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

    public function store(Request $request)
    {
        // dd($request->all()); 

        $validatedData = $request->validate([
            'txt_num_dossier' => 'required|string|unique:dossiers,txt_num_dossier',
            'txt_num_dordre' => 'required|numeric',
            'slt_service_rendu' => 'nullable|string',
            'txt_etat_cession' => 'nullable|string',
            'txt_cession_definitive' => 'nullable|string',
            'dt_date_creation' => 'required|date',

            // table Reference_Cadastral
            'rd_immatriculation_terrain' => 'required|string',
            'slt_dependant_domaine' => 'nullable|string',
            'ussu_bornage' => 'nullable|string', 
            'slt_lf' => 'nullable|string',
            'txt_num_requisition' => 'nullable|string',
            'txt_surface_bornage' => 'nullable|string',
            'dt_date_bornage' => 'nullable|date',
            'txt_nom_geometre' => 'nullable|string',

            // table Titulaire
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
                // Personne physique
            'slt_categoriePM' => 'nullable|string',
            'txt_formJuridiquePM' => 'nullable|string', 
            'txt_denominationPM' => 'nullable|string', 
            'txt_nineaPM' => 'nullable|string', 
            'txt_perssonneRepresentantPM' => 'nullable|string', 
            'txt_fonctionPM' => 'nullable|string', 
            'txt_telephonePM' => 'nullable|string', 
            'txt_emailPM' => 'nullable|string', 
            'txt_adressePM' => 'nullable|string', 
                // Personne morale
            'slt_etablissementPA' => 'nullable|string',
            'txt_personneResponsablePA' => 'nullable|string',
            'txt_fonctionResponsablePA' => 'nullable|string',
            'txt_telephonePA' => 'nullable|string',
            'txt_emailPA' => 'nullable|string',
            'txt_ministèreTutelePA' => 'nullable|string',

            'fichierPDF' => 'nullable|file|mimes:pdf|max:120400',
          
            // table Terrain 
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
            'txt_nicad' => 'nullable|string|unique:terrains,txt_nicad', 
            'slt_region' => 'required|exists:regions,id',
            'slt_departement' => 'required|exists:departements,id',
            'slt_arrondissement' => 'required|exists:arrondissements,id',
            'slt_commune' => 'required|exists:communes,id', 


        ], [
          
            'txt_num_dossier.required' => 'Numéro dossier est obligatoire',
            'txt_num_dordre.required' => 'Numéro d\'ordre est obligatoire',
            'slt_region.required' => 'Region est Obligatoire',
            'slt_departement.required' => 'Departement requis',
            'slt_arrondissement.required' => 'Arrondissement requis',
            'slt_commune.required'=> 'Commune requis', 
            'dt_date_creation.required' => 'Date de creation est obligatoire',
            'dt_date_delivrance.after' => 'La date de délivrance doit être postérieure à la date de naissance.',
            'txt_nicad.unique' => 'Ce NICAD existe déjà dans la base.',
            'slt_titulaire.required' => 'Titulaire est Obligatoire', 
            'rd_immatriculation_terrain.required' => 'Immatriculation Terrain est obligatoire', 
            'fichierPDF.file' => 'Le fichier sélectionné est invalide.',
            'fichierPDF.mimes' => 'Le fichier doit être au format PDF.',
            'fichierPDF.max' => 'La taille du fichier ne doit pas dépasser 120 Mo.',

        ]);     

            $region = Region::find($request->slt_region);
            $departement = Departement::find($request->slt_departement);
            $arrondissement = Arrondissement::find($request->slt_arrondissement);
            $commune = Commune::find($request->slt_commune);

 
            if (!$region || !$departement || !$arrondissement || !$commune) {
                return back()->with('danger', 'Une des relations est introuvable.');
            }
            
            // traitement fichier PDF
            if ($request->hasFile('fichierPDF')) {
                $validatedData['fichierPDF'] = $request->file('fichierPDF')->store('numerisation', 'public');
            }

 
            $dossier = Dossier::create([
                'txt_num_dossier' =>  $validatedData['txt_num_dossier'], 
                'txt_num_dordre' => $validatedData['txt_num_dordre'],  // numero d/'incementer 
                'slt_service_rendu' => $validatedData['slt_service_rendu'] ?? null,
                'txt_etat_cession' => $validatedData['txt_etat_cession'] ?? null,
                'txt_cession_definitive' => $validatedData['txt_cession_definitive'] ?? null,
                'dt_date_creation' => $validatedData['dt_date_creation'],
            ]);
  
            $referenceCadastrale = ReferenceCadastrale::create([   
                'rd_immatriculation_terrain' => $validatedData['rd_immatriculation_terrain'],
                'slt_dependant_domaine' => $validatedData['slt_dependant_domaine'] ?? null, 
                'ussu_bornage' => $validatedData['ussu_bornage'] ?? null, 
                'slt_lf' => $validatedData['slt_lf'] ?? null,
                'txt_num_requisition' => $validatedData['txt_num_requisition'] ?? null,
                'txt_surface_bornage' => $validatedData['txt_surface_bornage'] ?? null,
                'dt_date_bornage' =>  $validatedData['dt_date_bornage'] ?? null,
                'txt_nom_geometre' => $validatedData['txt_nom_geometre'] ?? null,
            ]);
    
            $titulaire = Titulaire::create([
                'slt_titulaire' => $validatedData['slt_titulaire'],
                'txt_nationalite' => $validatedData['txt_nationalite'] ?? null,
                'slt_civilite' => $validatedData['slt_civilite'] ?? null,
                'txt_prenom' => $validatedData['txt_prenom'] ?? null,
                'txt_nom' => $validatedData['txt_nom'] ?? null,
                'slt_piece' => $validatedData['slt_piece'] ?? null,
                'txt_numPiece' => $validatedData['txt_numPiece'] ?? null,
                'dt_date_delivrance' => $validatedData ['dt_date_delivrance'] ?? null,
                'dt_date_naissance' => $validatedData ['dt_date_naissance'] ?? null,
                'txt_lieu_naissance' => $validatedData['txt_lieu_naissance'] ?? null,
                'txt_adresse' => $validatedData['txt_adresse'] ?? null,
                'tel_telephone' => $validatedData['tel_telephone'] ?? null,
                'eml_email' => $validatedData['eml_email'] ?? null,
                'txt_representant' => $validatedData['txt_representant'] ?? null,
                'tel_telRepresentant' => $validatedData['tel_telRepresentant'] ?? null,
                    // Personne physique
                'slt_categoriePM'   => $validatedData['slt_categoriePM'] ?? null,
                'txt_formJuridiquePM' => $validatedData['txt_formJuridiquePM'] ?? null, 
                'txt_denominationPM' => $validatedData['txt_denominationPM'] ?? null, 
                'txt_nineaPM' => $validatedData['txt_nineaPM'] ?? null, 
                'txt_perssonneRepresentantPM' => $validatedData['txt_perssonneRepresentantPM'] ?? null, 
                'txt_fonctionPM' => $validatedData['txt_fonctionPM'] ?? null, 
                'txt_telephonePM' => $validatedData['txt_telephonePM'] ?? null, 
                'txt_emailPM' => $validatedData['txt_emailPM'] ?? null, 
                'txt_adressePM' => $validatedData['txt_adressePM'] ?? null, 
                    // Personne morale
                'slt_etablissementPA' => $validatedData['slt_etablissementPA'] ?? null,
                'txt_personneResponsablePA' => $validatedData['txt_personneResponsablePA'] ?? null,
                'txt_fonctionResponsablePA' => $validatedData['txt_fonctionResponsablePA'] ?? null,
                'txt_telephonePA' => $validatedData['txt_telephonePA'] ?? null,
                'txt_emailPA' => $validatedData['txt_emailPA'] ?? null,
                'txt_ministèreTutelePA' => $validatedData['txt_ministèreTutelePA'] ?? null,

                'fichierPDF'=> $validatedData['fichierPDF'] ?? null,
            ]);
        
            // table Terrain
            $terrain = Terrain::create([
                        
                'txt_lotissement' => $validatedData['txt_lotissement'] ?? null,
                'txt_HorsLotissement' => $validatedData['txt_HorsLotissement'] ?? null,
                'txt_num_lotissement' => $validatedData['txt_num_lotissement'] ?? null,
                'txt_num_section' => $validatedData['txt_num_section'] ?? null,
                'txt_num_parcelle' => $validatedData['txt_num_parcelle'] ?? null, 
                'nbr_surface' => $validatedData['nbr_surface'] ?? null,
                'txt_num_titre' => $validatedData['txt_num_titre'] ?? null,
                'txt_titre_mere' => $validatedData['txt_titre_mere'] ?? null,          
                'txt_appartement' => $validatedData['txt_appartement'] ?? null,  
                'slt_document_admin' => $validatedData['slt_document_admin'] ?? null,
                'txt_num_deliberation' => $validatedData['txt_num_deliberation'] ?? null,
                'dt_date_deliberation' => $validatedData['dt_date_deliberation'] ?? null,
                'txt_nicad' => $validatedData['txt_nicad'] ?? null,

                // Clés étrangères
                'slt_region' => $region->id,
                'slt_departement' => $departement->id,
                'slt_arrondissement' => $arrondissement->id,
                'slt_commune' => $commune->id,

                'txt_num_dossier' => $dossier->id, // 👍 Correct
                'reference_cadastrale_id' => $referenceCadastrale->id,
                'titulaire_id' => $titulaire->id,
            ]);
 
            return redirect()->route('secretariat.create')->with([
                'nbr_surface' => $terrain->nbr_surface,
                'txt_nicad' => $terrain->txt_nicad,
                'txt_num_dossier' => $dossier->txt_num_dossier,
            ]);

            
    }

    public function update(Request $request, $id)
    {

 
   
        $terrain = Terrain::findOrFail($id); 
        // ✅ Mise à jour du terrain (champs directs)
        $terrain->update($request->only([
            'txt_nicad',
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
  
            $titulaireData = $request->only([
                'slt_titulaire',
                'txt_nationalite',
                'slt_civilite',
                'txt_prenom',
                'txt_nom',
                'slt_piece',
                'txt_numPiece',
                'dt_date_delivrance',
                'dt_date_naissance',
                'txt_lieu_naissance',
                'txt_adresse',
                'tel_telephone',
                'txt_ninea',
                'eml_email', 
                'txt_representant',
                'tel_telRepresentant',
                "fichierPDF"
            ]);
 
            // ✅ MAJ ici
            $terrain->titulaire->update($titulaireData);
  
        }
         
        // ✅ Mise à jour de la relation reference_usage
        if ($terrain->references_usages && $request->has('occupants')) {
            foreach ($request->input('occupants') as $occupantData) {
                ReferenceUsage::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad, // ou autre champ unique
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
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

        // ✅ Mise à jour de la relation evaluation_amenagements
        if ($terrain->evaluations_amenagements && $request->has('occupantsAP')) {
            foreach ($request->input('occupantsAP') as $occupantData) {
                EvaluationAmenagement::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'nbr_valeur_totale_ap' => $request->input('nbr_valeur_totale_ap') ?? 0,
                    ], $occupantData)
                );

            }
        }
 
        return redirect()->route('donnee.create')->with('success', 'Modification réussi !'); 
 
    }

}
