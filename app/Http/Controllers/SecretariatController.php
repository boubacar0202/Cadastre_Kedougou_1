<?php

namespace App\Http\Controllers;

use App\Models\Donnee;
use App\Models\Region;
use App\Models\Departement;
use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Dossier;
use App\Models\ReferenceCadastrale;
use App\Models\Terrain;
use App\Models\Titulaire;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;


class SecretariatController extends Controller
{

    //
    public function index()
    {
        $donnee = Donnee::all();
        // $people = Person::with(['address', 'profession'])->get();
        // return view('people.index', compact('people'));

        return Inertia::render("secretariat/index",  ["donnees" => $donnee]);
    }

    public function create()
    {
        $regions = Region::all();

        return Inertia::render("secretariat/create",  [
            "regions" => $regions,
            "departements" => [],
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        //   $validatedData = $request->all();

        $validatedData = $request->validate([
            'txt_num_dossier' => 'required|string|unique:dossiers',
            'txt_num_dordre' => 'required|integer',
            'slt_service_rendu' => 'required|string',
            'txt_etat_cession' => 'required|string',
            'txt_cession_definitive' => 'required|string',
            'dt_date_creation' => 'required|date',

            //table Titulaire_terrain (table association entre Titulaire <=> Terrain)
            //'titulaire_id'
            //'terrain_id'

            //table Terrain
            // 'numDossier' => 'required|exists:dossiers,id',
            'txt_lotissement' => 'nullable|string',
            'txt_num_lotissement' => 'nullable|string',
            'txt_num_section' => 'nullable|string',
            'txt_num_parcelle' => 'nullable|string',
            'txt_num_titre' => 'nullable|string',
            'nbr_surface' => 'required|numeric|min:0|regex:/^\d{1,8}(\.\d{1,2})?$/',
            'slt_document_admin' => 'nullable|string',
            'txt_num_deliberation' => 'nullable|string',
            'dt_date_deliberation' => 'nullable|date',
            'txt_nicad' => 'nullable|string',
            // 'slt_region' => '',
            // 'slt_departement' => '',
            // 'slt_arrondissement' => '',
            // 'slt_commune' => '',
            'slt_region' => 'required|exists:regions,id',
            'slt_departement' => 'required|exists:departements,id',
            'slt_arrondissement' => 'required|exists:arrondissements,id',
            'slt_commune' => 'required|exists:commune,id',


            // table Reference_Cadastral
            // 'activeTab' => 'required|string',
            'rd_immatriculation_terrain' => 'nullable|string',
            'slt_dependant_domaine' => 'nullable|string',
            'ussu_bornage' => 'nullable|string',
            'txt_titre_mere' => 'nullable|string',
            'slt_lf' => 'nullable|string',
            'txt_num_requisition' => 'nullable|string',
            'txt_surface_bornage' => 'nullable|string',
            'dt_date_bornage' => 'nullable|date',
            'txt_nom_geometre' => 'nullable|string',

            // table Titulaire

            'slt_titulaire' => 'nullable|string',
            'txt_nationalite' => 'nullable|string',
            'slt_civilite' => 'nullable|string',
            'txt_prenom' => 'nullable|string',
            'txt_nom' => 'nullable|string',
            'slt_piece' => 'nullable|string',
            'txt_numPiece' => 'nullable|string',
            'dt_date_delivrance' => 'nullable|date',
            'dt_date_naissance' => 'nullable|date',
            'txt_lieu_naissance' => 'nullable|string',
            'txt_adresse' => 'nullable|string',
            'tel_telephone' => 'nullable|string',
            'txt_ninea' => 'nullable|string',
            'eml_email' => 'nullable|string',
            'txt_representant' => 'nullable|string',
            'tel_telRepresentant' => 'nullable|string',

            // table Localite

        ], [
            'txt_num_dossier.required' => 'Le numéro du dossier est requis.',
            'txt_num_dossier.unique' => 'Le numéro du dossier existe déjà.',

            'txt_num_dordre.required' => "Le numéro d'ordre est requis.",
            'txt_num_dordre.integer' => "Le numéro d'ordre doit être en chiffre.",

            'slt_service_rendu.required' => "Le service rendu est requis.",
            'txt_etat_cession.required' => "L etat Cession est requis.",
            'txt_cession_definitive.required' => "La cession definitive est requis.",
            'dt_date_creation.required' => "La date de création est requis.",
            'dt_date_creation.dt_date_creation' => "Veuillez choisir une date valide pour la date de création.",
            // Continuer ici l'affichage des erreurs

        ]);
        // dd(vars: $validatedData);
        // try {
        //     DB::beginTransaction();

        // // Vérification de la duplication du dossier
        // $dossierExist = Dossier::where('txt_num_dossier', $validatedData['txt_num_dossier'])->first();


        // if ($dossierExist) {
        //     return response()->json(['error' => 'Le dossier avec ce numéro existe déjà.']);
        // }

            // // Vérification de la duplication du terrain
            // $terrainExist = Terrain::where('txt_nicad', $validatedData['txt_nicad'])->first();
            // if ($terrainExist) {
            //     return response()->json(['error' => 'Le terrain avec ce NICAD existe déjà.']);
            // }
        



            Dossier::create([
                'numDossier' => $validatedData['numDossier'],
                'numDordre' => $validatedData['numDordre'],
                'serviceRendu' => $validatedData['serviceRendu'],
                'etatCession' => $validatedData['etatCession'],
                'cessionDefinitive' => $validatedData['cessionDefinitive'],
                'dateCreation' => $validatedData['dateCreation'],
            ]);
            
            // Récupération des relations (Vérification des IDs)
           
            // $region = Region::findOrFail($validatedData['slt_region']);
            // $departement = Departement::findOrFail($validatedData['slt_departement']);
            // $arrondissement = Arrondissement::findOrFail($validatedData['slt_arrondissement']);
            // $commune = Commune::findOrFail($validatedData['slt_commune']);
            // $dossier = Dossier::findOrFail($validatedData['numDossier']);

            // //table Terrain
            // Terrain::create([
            //     'dossier_id' => $dossier->id, // Correction ici
            //     'txt_lotissement' => $validatedData['txt_lotissement'] ?? null,
            //     'txt_num_lotissement' => $validatedData['txt_num_lotissement'] ?? null,
            //     'txt_num_section' => $validatedData['txt_num_section'] ?? null,
            //     'txt_num_parcelle' => $validatedData['txt_num_parcelle'] ?? null,
            //     'txt_num_titre' => $validatedData['txt_num_titre'] ?? null,
            //     'nbr_surface' => $validatedData['nbr_surface'] ?? null,
            //     'slt_document_admin' => $validatedData['slt_document_admin'] ?? null,
            //     'txt_num_deliberation' => $validatedData['txt_num_deliberation'] ?? null,
            //     'dt_date_deliberation' => $validatedData['dt_date_deliberation'] ?? null,
            //     'txt_nicad' => $validatedData['txt_nicad'] ?? null,
            //     'region_id' => $region->id,
            //     'departement_id' => $departement->id,
            //     'arrondissement_id' => $arrondissement->id,
            //     'commune_id' => $commune->id,
            // ]);

        // $terrain = Terrain::findOrFail(); // Récupère un terrain par son ID
        // $region = $terrain->region; // Accède à la région associée
        // $departement = $terrain->departement; // Accède au département associé
        // $arrondissement = $terrain->arrondissement; // Accède au arrondissement associé
        // $commune = $terrain->$commune;
        // $dossier = $terrain->$dossier;


        // return response()->json(['message' => 'Terrain créé avec succès', 'terrain' => $terrain], 201);

            // table Reference_Cadastral
            ReferenceCadastrale::create([
                'rd_immatriculation_terrain' => $validatedData['rd_immatriculation_terrain'] ?? null,
                'slt_dependant_domaine' => $validatedData['slt_dependant_domaine'] ?? null,
                'slt_bornage' => $validatedData['slt_bornage'] ?? null,
                'ussu_bornage' => $validatedData['ussu_bornage'] ?? null,
                'txt_titre_mere' => $validatedData['txt_titre_mere'] ?? null,
                'slt_lf' => $validatedData['slt_lf'] ?? null,
                'txt_num_requisition' => $validatedData['txt_num_requisition'] ?? null,
                'txt_surface_bornage' => $validatedData['txt_surface_bornage'] ?? null,
                'dt_date_bornage' => $validatedData['dt_date_bornage'] ?? null,
                'txt_nom_geometre' => $validatedData['txt_nom_geometre'] ?? null,
            ]);

            // table Titulaire
            Titulaire::create([
                'slt_titulaire' => $validatedData['slt_titulaire'],
                'txt_nationalite' => $validatedData['txt_nationalite'],
                'slt_civilite' => $validatedData['slt_civilite'],
                'txt_prenom' => $validatedData['txt_prenom'],
                'txt_nom' => $validatedData['txt_nom'],
                'slt_piece' => $validatedData['slt_piece'],
                'txt_numPiece' => $validatedData['txt_numPiece'],
                'dt_date_delivrance' => $validatedData['dt_date_delivrance'],
                'dt_date_naissance' => $validatedData['dt_date_naissance'],
                'txt_lieu_naissance' => $validatedData['txt_lieu_naissance'],
                'txt_adresse' => $validatedData['txt_adresse'],
                'tel_telephone' => $validatedData['tel_telephone'],
                'txt_ninea' => $validatedData['txt_ninea'] ?? null,
                'eml_email' => $validatedData['eml_email'] ?? null,
                'txt_representant' => $validatedData['txt_representant'] ?? null,
                'tel_telRepresentant' => $validatedData['tel_telRepresentant'],
            ]);

            // dd($validatedData);

        //  Model::create($validatedData);

        //     DB::commit();
        //     return redirect()->json(['Réussis'=> 'Dossier enregistré avec succès.']);
        // } catch (\Exception $e) {

        //     DB::rollback();
        //     return back()->json(['error' =>  $e->getMessage()]);
        // }

        return redirect()->back()->with('success', 'Donnée enregistrée !');

    }
}
