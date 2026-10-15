<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; 
use App\Models\Depart;
use App\Models\Arrivee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DepartController extends Controller
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
        return Inertia::render("depart/create");
    }

    /**
     * Store a newly created resource in storage.
    */

    // retrouver le dernier numéro dordre 
    public function getNextOrdre($annee)
    {
         
        $last = Depart::whereYear('dt_datecouriercd', $annee) // 🔥 ici on filtre par champ dt_datearrivee, pas created_at !
        ->orderByRaw('CAST(SUBSTRING_INDEX(txt_numdordrecd, "/", 1) AS UNSIGNED) DESC')
        ->first();
    
        $nextOrdre = $last ? ((int) explode('/', $last->txt_numdordrecd)[0] + 1) : 1;

        $numOrdre = str_pad($nextOrdre, 4, '0', STR_PAD_LEFT);
    
        return response()->json([
            'num_dordre' => $numOrdre,      // ex: "00001" 
        ]);
    }
 
    public function fetchReferencesArrivee(Request $request): JsonResponse
    {
        $dejaRepondues = Depart::query()
            ->whereNotNull('txt_referencecourierarriveecd')
            ->where('txt_referencecourierarriveecd', '!=', '')
            ->pluck('txt_referencecourierarriveecd');

        $arrivees = Arrivee::query()
            ->whereNotNull('txt_reference')
            ->where('txt_reference', '!=', '')
            ->when($dejaRepondues->isNotEmpty(), function ($query) use ($dejaRepondues) {
                $query->where(function ($q) use ($dejaRepondues) {
                    $q->where('txt_typecourrier', 'BE à réponses Multiples')
                        ->orWhereNotIn('txt_reference', $dejaRepondues);
                });
            })
            ->orderByDesc('dt_datearrivee')
            ->get()
            ->unique('txt_reference')
            ->values();

        $map = function (string $field) use ($arrivees) {
            return $arrivees->mapWithKeys(fn ($arrivee) => [
                $arrivee->txt_reference => $arrivee->{$field},
            ])->all();
        };

        return response()->json([
            'references' => $arrivees->pluck('txt_reference')->values()->all(),
            'map_ref_to_expediteur' => $map('txt_expediteur'),
            'map_ref_to_objet' => $map('txt_objet'),
            'map_ref_to_reception' => $map('txt_reference'),
            'map_ref_to_interesse' => $map('txt_prenomNom'),
            'map_ref_to_designation' => $map('txt_lotissement'),
            'map_ref_to_surface' => $map('txt_superficie'),
            'map_ref_to_numLotissement' => $map('txt_numLot'),
            'map_ref_to_nombrePiece' => $map('txt_nombrepiece'),
            'map_ref_to_prenomNom' => $map('txt_prenomNom'),
            'map_ref_to_numLot' => $map('txt_numLot'),
            'map_ref_to_superficie' => $map('txt_superficie'),
            'map_ref_to_numTF' => $map('txt_numTF'),
            'map_ref_to_lotissement' => $map('txt_lotissement'),
            'map_ref_to_typeCourrier' => $map('txt_typecourrier'),
            'map_ref_to_numDossier' => $map('txt_numdossier'),
        ]);
    }

    // Supprimer le fichier PDF
    public function deletePdf($id)
    {
        $depart = Depart::findOrFail($id);

        if ($depart->fichierPDFcd && Storage::disk('public')->exists($depart->fichierPDFcd)) {
            Storage::disk('public')->delete($depart->fichierPDFcd);
        }

        // Supprime le lien dans la base de données
        $depart->update(['fichierPDFcd' => null]);
 
        return back()->with('success', 'Fichier supprimé avec succès');
    }
    

    public function store(Request $request)
    {
        // ================= VALIDATION =================
        $validateData = $request->validate([
            'txt_numdordrecd' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    $annee = date('Y', strtotime($request->dt_datecouriercd));
                    $numPart = explode('/', $value)[0] ?? $value;

                    $exists = Depart::whereYear('dt_datecouriercd', $annee)
                                    ->where('txt_numdordrecd', 'like', $numPart . '/%')
                                    ->exists();

                    if ($exists) {
                        $fail("Le numéro $numPart existe déjà pour l'année $annee.");
                    }
                },
            ],
            'txt_numdossiercd'              => 'nullable|string|max:50',
            'txt_avisReponsecd'             => 'nullable|string|max:15',
            'txt_caracterecd'               => 'nullable|string|max:255',
            'dt_datecouriercd'              => 'required|date',
            'txt_typecourriercd'            => 'required|string|max:255',
            'txt_categoriecd'               => 'nullable|string|max:255',
            'txt_referencecourierarriveecd' => 'nullable|string|max:255',
            'txt_referencecourierdepartcd'  => 'nullable|string|max:255',
            'txt_nombrepiececd'             => 'required|integer|min:0|max:32767',
            'txt_referencecd'               => 'nullable|string|max:255',

            'txt_prenomNomcd'               => 'nullable|string|max:255',
            'txt_numLotcd'                  => 'nullable|string|max:50',      // ✅ Limite cohérente avec la BDD
            'txt_superficiecd'              => 'nullable|numeric|min:0|max:999999',      // ✅ Idem
            'txt_numTFcd'                   => 'nullable|string|max:20',
            'txt_lotissementcd'             => 'nullable|string|max:100',

            'txt_objetcd'                   => 'nullable|string|max:255',
            'txt_destinatairecd'            => 'required|string|max:255',
            'dt_dateenvoicd'                => 'required|date|before_or_equal:today',
            'txt_referencereceptioncd'      => 'nullable|string|max:255',
            'txt_observationcd'             => 'nullable|string|max:255',
            'txt_dureetraitementcd'         => 'nullable|string|max:255',
            'fichierPDFcd'                  => 'nullable|file|mimes:pdf|max:102400', // 100 Mo
        ], [
            // ================= MESSAGES PERSONNALISÉS =================
            'txt_numdordrecd.required'              => "Le numéro d'ordre est requis.",
            'txt_numdordrecd.max'                   => "Le numéro d'ordre ne doit pas dépasser 50 caractères.",
            'txt_avisReponsecd.max'                 => "L'avis ne doit pas dépasser 15 caractères.",
            'txt_numdossiercd.max'                  => "Le numéro de dossier ne doit pas dépasser 50 caractères.",
            'dt_datecouriercd.required'             => "La date du courrier est requise.",
            'dt_datecouriercd.date'                 => "La date du courrier doit être une date valide.",
            'txt_typecourriercd.required'           => "Le type de courrier est requis.",
            'txt_nombrepiececd.required'            => "Le nombre de pièces est requis.",
            'txt_nombrepiececd.integer'             => "Le nombre de pièces doit être un entier.",
            'txt_objetcd.required'                  => "L'objet du courrier est requis.",
            'txt_destinatairecd.required'           => "Le destinataire du courrier est requis.",
            'dt_dateenvoicd.required'               => "La date d'envoi est requise.",
            'dt_dateenvoicd.date'                   => "La date d'envoi doit être une date valide.",
            'dt_dateenvoicd.before_or_equal'        => "La date d'envoi ne peut pas être dans le futur.",
            'txt_numLotcd.max'                      => "Le numéro de lot ne doit pas dépasser 50 caractères.",
            'txt_superficiecd.max'                  => "La superficie ne doit pas dépasser 50 caractères.",
            'fichierPDFcd.file'                     => "Le fichier doit être un fichier valide.",
            'fichierPDFcd.mimes'                    => "Le fichier doit être au format PDF.",
            'fichierPDFcd.max'                      => "La taille du fichier ne doit pas dépasser 100 Mo.",
        ]);

        // ================= FICHIER PDF =================
        if ($request->hasFile('fichierPDFcd')) {
            $validateData['fichierPDFcd'] = $request->file('fichierPDFcd')
                ->store('courrierdepart', 'public');
        }

        // ================= CRÉATION =================
        Depart::create([
            'txt_numdordrecd'               => $validateData['txt_numdordrecd'],
            'txt_numdossiercd'              => $validateData['txt_numdossiercd'] ?? null,
            'txt_avisReponsecd'             => $validateData['txt_avisReponsecd'] ?? null,
            'txt_caracterecd'               => $validateData['txt_caracterecd'] ?? null,
            'dt_datecouriercd'              => $validateData['dt_datecouriercd'],
            'txt_typecourriercd'            => $validateData['txt_typecourriercd'],
            'txt_categoriecd'               => $validateData['txt_categoriecd'] ?? null,
            'txt_referencecourierarriveecd' => $validateData['txt_referencecourierarriveecd'] ?? null,
            'txt_referencecourierdepartcd'  => $validateData['txt_referencecourierdepartcd'] ?? null,
            'txt_nombrepiececd'             => $validateData['txt_nombrepiececd'],
            'txt_referencecd'               => $validateData['txt_referencecd'] ?? null,
            'txt_prenomNomcd'               => $validateData['txt_prenomNomcd'] ?? null,
            'txt_numLotcd'                  => $validateData['txt_numLotcd'] ?? null,
            'txt_superficiecd'              => $validateData['txt_superficiecd'] ?? null,
            'txt_numTFcd'                   => $validateData['txt_numTFcd'] ?? null,
            'txt_lotissementcd'             => $validateData['txt_lotissementcd'] ?? null,
            'txt_objetcd'                   => $validateData['txt_objetcd'] ?? null,
            'txt_destinatairecd'            => $validateData['txt_destinatairecd'],
            'dt_dateenvoicd'                => $validateData['dt_dateenvoicd'],
            'txt_referencereceptioncd'      => $validateData['txt_referencereceptioncd'] ?? null,
            'txt_observationcd'             => $validateData['txt_observationcd'] ?? null,
            'txt_dureetraitementcd'         => $validateData['txt_dureetraitementcd'] ?? null,
            'fichierPDFcd'                  => $validateData['fichierPDFcd'] ?? null,
        ]);

        return redirect()
            ->route('depart.create')
            ->with('success', 'Dossier enregistré avec succès');
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
    public function edit($id)
    {
        //
        $departs = Depart::findOrFail($id); 
 
        return Inertia::render('depart/editdepart', [
            'departs' => $departs,   
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update (Request $request, string $id)
    {
        //
        $depart = Depart::findOrFail($id); 
  
        $validateData   = $request->validate([  
            // 'txt_numdordrecd' => 'required|unique:departs,txt_numdordrecd'.$id,  
            'txt_numdordrecd' => [
                'required',
                Rule::unique('departs', 'txt_numdordrecd')->ignore($depart->id),
            ],
            'txt_caracterecd' => 'nullable|string|max:255', 
            'txt_numdossiercd' => 'nullable|string|max:255',
            'txt_avisReponsecd' => 'nullable|string|max:15',
            'dt_datecouriercd' => 'required|date',
            'txt_typecourriercd' => 'required|string|max:255',
            'txt_categoriecd' => 'required|string|max:255',
            'txt_referencecourierarriveecd' => 'nullable|string|max:255',
            'txt_referencecourierdepartcd'=> 'nullable|string|max:255',
            'txt_nombrepiececd' => 'required|integer|max:255',
            'txt_referencecd' => 'nullable|string|max:255',
            'txt_prenomNomcd' => 'nullable|string|max:255',
            'txt_numLotcd' => 'nullable|string|max:255',
            'txt_superficiecd' => 'nullable|string|max:255',
            'txt_numTFcd' => 'nullable|string|max:255',
            'txt_lotissementcd' => 'nullable|string|max:255',
            'txt_objetcd' => 'required|string|max:255',
            'txt_destinatairecd' => 'required|string|max:255',
            'dt_dateenvoicd' => 'required|date|before_or_equal:today',
            'txt_referencereceptioncd' => 'nullable|string|max:255',
            'txt_observationcd' => 'nullable|string|max:255',
            'txt_dureetraitementcd' => 'nullable|string|max:255', 
            'fichierPDFcd' => 'nullable|file|mimes:pdf|max:120400',  // 100Mo * 100 x 1024
        ],[
            'txt_numdordrecd.required' => 'Le numéro d\'ordre est requis.',
            'txt_numdordrecd.unique' => 'Le numéro d\'ordre doit être unique.', 
            'dt_datecouriercd.required' => 'La date du courrier est requise.',
            'txt_avisReponsecd.max' => 'L\'avis ne doit pas dépasser 15 caractères', 
            'dt_datecouriercd.date' => 'La date du courrier doit être une date valide.',
            'txt_typecourriercd.required' => 'Le type de courrier est requis.',
            'txt_categoriecd.required' => 'La catégorie du courrier est requise.',
            'txt_nombrepiececd.required' => 'Le nombre de pièces est requis.',
            'txt_objetcd.required' => 'L\'objet du courrier est requis.',
            'txt_destinatairecd.required' => 'Le destinataire du courrier est requis.',
            'dt_dateenvoicd.required' => 'La date d\'envoi est requise.',
            'dt_dateenvoicd.date' => 'La date d\'envoi doit être une date valide.',
            'dt_dateenvoicd.before_or_equal' => 'La date d\'envoi ne peut pas être dans le futur.',
            'fichierPDFcd.file' => 'Le fichier doit être un fichier valide.',
            'fichierPDFcd.mimes' => 'Le fichier doit être au format PDF.',
            'fichierPDFcd.max' => 'La taille du fichier ne doit pas dépasser 100 Mo.',
        ]);
       
        // ✅ GESTION DU FICHIER COMME DANS STORE()
        if ($request->hasFile('fichierPDFcd')) {
            // Supprimer l'ancien fichier si existe
            if ($depart->fichierPDFcd && Storage::disk('public')->exists($depart->fichierPDFcd)) {
                Storage::disk('public')->delete($depart->fichierPDFcd);
            }
            
            // Stocker le nouveau fichier DANS LE MÊME DOSSIER
            $validateData['fichierPDFcd'] = $request->file('fichierPDFcd')->store('courrierdepart', 'public');
        } else {
            // Garder l'ancien fichier si aucun nouveau n'est uploadé
            $validateData['fichierPDFcd'] = $depart->fichierPDFcd;
        }

        // ✅ Mise à jour du arrivee (champs directs)
        $depart->update($validateData);
        
        return redirect()->route('instancedepart.create')->with('success', 'Courrier '.$depart->txt_numdordrecd.' modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
