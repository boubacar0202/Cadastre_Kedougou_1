<?php

namespace App\Http\Controllers;

use App\Models\Arrivee; 
use Illuminate\Http\Request;
use Inertia\Inertia;  
use Illuminate\Support\Facades\Storage; 


class ArriveeController extends Controller
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
 
    public function getNextOrdre($annee)
    {
 
        $last = Arrivee::whereYear('dt_datearrivee', $annee)
        ->orderByRaw('CAST(SUBSTRING_INDEX(txt_numdordre, "/", 1) AS UNSIGNED) DESC')
        ->first();
    
        $nextOrdre = $last ? ((int) explode('/', $last->txt_numdordre)[0] + 1) : 1;

        $numOrdre = str_pad($nextOrdre, 5, '0', STR_PAD_LEFT);
    
        return response()->json([
            'num_dordre' => $numOrdre,      // ex: "00001" 
        ]);
    }
      
    public function create()
    {
        //
        return Inertia::render("arrivee/create");    
    }

    // Supprimer le fichier PDF
    public function deletePdf($id)
    {
        $arrivee = Arrivee::findOrFail($id);

        if ($arrivee->fichierPDF && Storage::disk('public')->exists($arrivee->fichierPDF)) {
            Storage::disk('public')->delete($arrivee->fichierPDF);
        }

        // Supprime le lien dans la base de données
        $arrivee->update(['fichierPDF' => null]);
 
        return back()->with('success', 'Fichier supprimé avec succès');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation – on retire 'unique' et on met la règle personnalisée dans le bon endroit
        $validateData = $request->validate([
            'txt_numdordre' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    $annee = date('Y', strtotime($request->dt_datearrivee));
                    $numPart = explode('/', $value)[0] ?? $value;

                    $exists = Arrivee::whereYear('dt_datearrivee', $annee)
                                    ->where('txt_numdordre', 'like', $numPart . '/%')
                                    ->exists();

                    if ($exists) {
                        $fail("Le numéro $numPart existe déjà pour l'année $annee.");
                    }
                },
            ],
            'txt_numbordereau'   => 'nullable|string|max:255',
            'txt_numdossier'     => 'nullable|string|max:255',
            'txt_caractere'      => 'nullable|string|max:255',
            'txt_typecourrier'   => 'required|string|max:255',
            'dt_datearrivee'     => 'required|date',
            'txt_numcourier'     => 'required|string|max:255',
            'dt_datecourier'     => 'required|date|before_or_equal:today',
            'txt_reference'      => 'required|unique:arrivees,txt_reference|string|max:255',
            'txt_categorie'      => 'required|string|max:255',
            'txt_designation'    => 'required|string|max:255',
            'txt_prenomNom'      => 'nullable|string|max:255',
            'txt_numLot'         => 'nullable|string|max:255',
            'txt_superficie'     => 'nullable|string|max:255',
            'txt_numTF'          => 'nullable|string|max:255',
            'txt_lotissement'    => 'nullable|string|max:255',
            'dt_date'            => 'nullable|date',
            'txt_heure'          => 'nullable|string|max:255',
            'txt_lieu'           => 'nullable|string|max:255',
            'txt_nombrepiece'    => 'required|integer',
            'txt_objet'          => 'required|string|max:255',
            'txt_expediteur'     => 'required|string|max:255',
            'txt_agenttraiteur'  => 'required|string|max:255',
            'txt_observation'    => 'nullable|string|max:255',
            'fichierPDF'         => 'nullable|file|mimes:pdf|max:120400',
        ], [
            // Messages d’erreur (sans les règles, uniquement des chaînes)
            'txt_numdordre.required'   => 'Le numéro d\'ordre est requis.',
            'txt_reference.required'   => 'La référence est requise.',
            'txt_reference.unique'     => 'La référence existe déjà.',
            'txt_numcourier.required'  => 'Le numéro du courrier est requis.',
            'dt_datearrivee.required'  => 'La date d\'arrivée est requise.',
            'dt_datearrivee.date'      => 'Le format de la date d\'arrivée est invalide.',
            'dt_datecourier.required'  => 'La date du courrier est requise.',
            'dt_datecourier.before_or_equal' => 'La date du courrier ne peut pas dépasser aujourd\'hui.',
            'txt_categorie.required'   => 'La catégorie est requise.',
            'txt_designation.required' => 'La désignation est requise.',
            'txt_typecourrier.required'=> 'Le type de courrier est requis.',
            'txt_nombrepiece.required' => 'Le nombre de pièces est requis.',
            'txt_nombrepiece.integer'  => 'Le nombre de pièces doit être un entier.',
            'txt_objet.required'       => 'L\'objet est requis.',
            'txt_expediteur.required'  => 'L\'expéditeur est requis.',
            'txt_agenttraiteur.required' => 'L\'agent traitant est requis.',
            'fichierPDF.mimes'         => 'Le fichier doit être au format PDF.',
            'fichierPDF.max'           => 'La taille du fichier ne doit pas dépasser 120 Mo.',
        ]);

        // Gestion du fichier PDF 
        if ($request->hasFile('fichierPDF')) {
            $validateData['fichierPDF'] = $request->file('fichierPDF')->store('courrierarrivee', 'public');
        }

        Arrivee::create([
            'txt_numdordre' => $validateData['txt_numdordre'],  
            'txt_numbordereau' => $validateData['txt_numbordereau'] ?? null,
            'txt_numdossier' => $validateData['txt_numdossier'] ?? null,
            'txt_caractere' => $validateData['txt_caractere'] ?? null,
            'txt_typecourrier' => $validateData['txt_typecourrier'],
            'dt_datearrivee' => $validateData['dt_datearrivee'],
            'txt_numcourier' => $validateData['txt_numcourier'],
            'dt_datecourier' => $validateData['dt_datecourier'],
            'txt_reference' => $validateData['txt_reference'],
            'txt_categorie' => $validateData['txt_categorie'],
            'txt_designation' => $validateData['txt_designation'],
            'txt_prenomNom' => $validateData['txt_prenomNom'] ?? null,
            'txt_numLot' => $validateData['txt_numLot'] ?? null,
            'txt_superficie' => $validateData['txt_superficie'] ?? null,
            'txt_numTF' => $validateData['txt_numTF'] ?? null,
            'txt_lotissement' => $validateData['txt_lotissement'] ?? null,  
            'dt_date' => $validateData['dt_date'] ?? null,  
            'txt_heure' => $validateData['txt_heure'] ?? null,
            'txt_lieu' => $validateData['txt_lieu'] ?? null,
            'txt_nombrepiece' => $validateData['txt_nombrepiece'],
            'txt_objet' => $validateData['txt_objet'],
            'txt_expediteur' => $validateData['txt_expediteur'],
            'txt_agenttraiteur' => $validateData['txt_agenttraiteur'],
            'txt_observation' => $validateData['txt_observation'] ?? null, 
            'fichierPDF'=> $validateData['fichierPDF'] ?? null,
        ]);

        return redirect()->route('arrivee.create')->with('success', 'Courrier arrivée créé avec succès');
  
    }
 
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrivees = Arrivee::findOrFail($id);

        return Inertia::render('arrivee/editarrivee', [
            'arrivees' => $arrivees,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    { 
        $arrivee = Arrivee::findOrFail($id); 

        // ✅ UTILISEZ LES MÊMES RÈGLES DE VALIDATION QUE STORE()
        $validateData = $request->validate([
            'txt_numdordre' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request, $id) {
                    $annee = date('Y', strtotime($request->dt_datearrivee));
                    $numPart = explode('/', $value)[0] ?? $value;

                    $exists = Arrivee::whereYear('dt_datearrivee', $annee)
                        ->where('txt_numdordre', 'like', $numPart . '/%')
                        ->where('id', '!=', $id)
                        ->exists();

                    if ($exists) {
                        $fail("Le numéro $numPart existe déjà pour l'année $annee.");
                    }
                },
            ],
            'txt_numbordereau' => 'nullable|string|max:255',
            'txt_numdossier' => 'nullable|string|max:255',
            'txt_caractere' => 'nullable|string|max:255',
            'txt_typecourrier' => 'required|string|max:255',
            'dt_datearrivee' => 'required|date',
            'txt_numcourier' => 'required|string|max:255',
            'dt_datecourier' => 'required|date|before_or_equal:today',
            'txt_reference' => 'required|string|max:255|unique:arrivees,txt_reference,' . $id,
            'txt_categorie' => 'required|string|max:255',
            'txt_designation' => 'required|string|max:255',
            'txt_prenomNom' => 'nullable|string|max:255',
            'txt_numLot' => 'nullable|string|max:255',
            'txt_superficie' => 'nullable|string|max:255',
            'txt_numTF' => 'nullable|string|max:255',
            'txt_lotissement' => 'nullable|string|max:255',
            'dt_date' => 'nullable|date',
            'txt_heure' => 'nullable|string|max:255',
            'txt_lieu' => 'nullable|string|max:255',
            'txt_nombrepiece' => 'required|integer',
            'txt_objet' => 'required|string|max:255',
            'txt_expediteur' => 'required|string|max:255',
            'txt_agenttraiteur' => 'required|string|max:255',
            'txt_observation' => 'nullable|string|max:255',
            'fichierPDF' => 'nullable|file|mimes:pdf|max:120400',
        ], [
            // ... mêmes messages d'erreur que store() ...
            'txt_numdordre.required' => 'Le numéro d\'ordre est requis.',
            'txt_numdordre.unique' => 'Le numéro d\'ordre doitêtre unique.',
            'dt_datearrivee.required' => 'La date d\'arrivée est requise.',
            'dt_datearrivee.date' => 'La date d\'arrivée doitêtre une date valide.',
            'txt_typecourrier.required' => 'Le type de courrier est requis.',
            'txt_numcourier.required' => 'Le numéro du courrier est requis.',
            'dt_datecourier.required' => 'La date du courrier est requise.',
            'dt_datecourier.date' => 'La date du courrier doitêtre une date valide.',
            'txt_reference.required' => 'La reference est requise.',
            'txt_categorie.required' => 'La catégorie du courrier est requise.',
            'txt_designation.required' => 'La designation est requise.',
            'txt_nombrepiece.required' => 'Le nombre de pièces est requis.',
            'txt_objet.required' => 'L\'objet du courrier est requis.',
            'txt_expediteur.required' => 'L\'expéditeur du courrier est requis.',
            'txt_agenttraiteur.required' => 'L\'agent traiteur du courrier est requis.',
     
        ]);
        
        // ✅ GESTION DU FICHIER COMME DANS STORE()
        if ($request->hasFile('fichierPDF')) {
            // Supprimer l'ancien fichier si existe
            if ($arrivee->fichierPDF && Storage::disk('public')->exists($arrivee->fichierPDF)) {
                Storage::disk('public')->delete($arrivee->fichierPDF);
            }
            
            // Stocker le nouveau fichier DANS LE MÊME DOSSIER
            $validateData['fichierPDF'] = $request->file('fichierPDF')->store('courrierarrivee', 'public');
        } else {
            // Garder l'ancien fichier si aucun nouveau n'est uploadé
            $validateData['fichierPDF'] = $arrivee->fichierPDF;
        }

        // ✅ MISE À JOUR COMME DANS STORE()
        $arrivee->update([
            'txt_numdordre' => $validateData['txt_numdordre'],  
            'txt_numbordereau' => $validateData['txt_numbordereau'] ?? null,
            'txt_numdossier' => $validateData['txt_numdossier'] ?? null,
            'txt_caractere' => $validateData['txt_caractere'] ?? null,
            'txt_typecourrier' => $validateData['txt_typecourrier'],
            'dt_datearrivee' => $validateData['dt_datearrivee'],
            'txt_numcourier' => $validateData['txt_numcourier'],
            'dt_datecourier' => $validateData['dt_datecourier'],
            'txt_reference' => $validateData['txt_reference'],
            'txt_categorie' => $validateData['txt_categorie'],
            'txt_designation' => $validateData['txt_designation'],
            'txt_prenomNom' => $validateData['txt_prenomNom'] ?? null,
            'txt_numLot' => $validateData['txt_numLot'] ?? null,
            'txt_superficie' => $validateData['txt_superficie'] ?? null,
            'txt_numTF' => $validateData['txt_numTF'] ?? null,
            'txt_lotissement' => $validateData['txt_lotissement'] ?? null,
            'dt_date' => $validateData['dt_date'] ?? null,  
            'txt_heure' => $validateData['txt_heure'] ?? null,
            'txt_lieu' => $validateData['txt_lieu'] ?? null,
            'txt_nombrepiece' => $validateData['txt_nombrepiece'],
            'txt_objet' => $validateData['txt_objet'],
            'txt_expediteur' => $validateData['txt_expediteur'],
            'txt_agenttraiteur' => $validateData['txt_agenttraiteur'],
            'txt_observation' => $validateData['txt_observation'] ?? null, 
            'fichierPDF' => $validateData['fichierPDF'] ?? null,
        ]);

        return redirect()->route('arrivee.create')
            ->with('success', 'Courrier '.$arrivee->txt_numdordre.' modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
