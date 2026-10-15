<?php

namespace App\Http\Controllers;

use App\Models\CodeAcces;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ServiceController extends Controller
{
    /* ---------------------------------------------------------------- */
    /* INDEX                                                             */
    /* ---------------------------------------------------------------- */
    public function index()
    {
        return Inertia::render('parametre/service/Index');
    }

    /* ---------------------------------------------------------------- */
    /* CREATE                                                            */
    /* ---------------------------------------------------------------- */
    public function create()
    {
        $services = Service::orderBy('id', 'desc')->get();

        return Inertia::render('parametre/service/create', [
            'services' => $services,
        ]);
    }

    /* ---------------------------------------------------------------- */
    /* STORE                                                             */
    /* ---------------------------------------------------------------- */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'txt_numeroNote' => 'unique:services,txt_numeroNote|required|string|max:255',
            'txt_typeNote'   => 'required|string|max:255',
            'txt_titreNote'  => 'required|string|max:255',
            'fichierPDF'     => 'nullable|file|mimes:pdf|max:120400',
        ], [
            'txt_numeroNote.required' => 'Le numéro de note est requis.',
            'txt_numeroNote.unique'   => 'Ce numéro de note existe déjà.',
            'txt_typeNote.required'   => 'Le type de note est requis.',
            'txt_titreNote.required'  => 'Le titre de note est requis.',
            'fichierPDF.mimes'        => 'Le fichier doit être au format PDF.',
            'fichierPDF.max'          => 'La taille du fichier ne doit pas dépasser 120 Mo.',
        ]);

        if ($request->hasFile('fichierPDF')) {
            $validated['fichierPDF'] = $request->file('fichierPDF')->store('service', 'public');
        }

        Service::create([
            'txt_numeroNote' => $validated['txt_numeroNote'],
            'txt_typeNote'   => $validated['txt_typeNote'],
            'txt_titreNote'  => $validated['txt_titreNote'],
            'fichierPDF'     => $validated['fichierPDF'] ?? null,
        ]);

        return redirect()->route('parametre.service.create')
            ->with('success', 'Note de service créée avec succès');

    }

    /* ---------------------------------------------------------------- */
    /* EDIT                                                              */
    /* ---------------------------------------------------------------- */
    public function edit(Service $service)
    {
        return Inertia::render('parametre/service/edit', [
            'service'  => $service,
            'services' => Service::all(),
        ]);
    }

    /* ---------------------------------------------------------------- */
    /* SHOW (à supprimer si non utilisé)                                 */
    /* ---------------------------------------------------------------- */
    public function show(Service $service)
    {
        return Inertia::render('parametre/service/Show', [
            'service' => $service,
        ]);
    }

    /* ---------------------------------------------------------------- */
    /* UPDATE                                                            */
    /* ---------------------------------------------------------------- */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'txt_numeroNote' => 'required|string|max:255|unique:services,txt_numeroNote,' . $service->id,
            'txt_typeNote'   => 'required|string|max:255',
            'txt_titreNote'  => 'required|string|max:255',
            'fichierPDF'     => 'nullable|file|mimes:pdf|max:102400',
        ], [
            'txt_numeroNote.required' => 'Le numéro de note est requis.',
            'txt_numeroNote.unique'   => 'Ce numéro de note existe déjà.',
            'txt_typeNote.required'   => 'Le type de note est requis.',
            'txt_titreNote.required'  => 'Le titre de note est requis.',
            'fichierPDF.mimes'        => 'Le fichier doit être au format PDF.',
            'fichierPDF.max'          => 'La taille du fichier ne doit pas dépasser 100 Mo.',
        ]);

        if ($request->hasFile('fichierPDF')) {
            if ($service->fichierPDF && Storage::disk('public')->exists($service->fichierPDF)) {
                Storage::disk('public')->delete($service->fichierPDF);
            }

            $validated['fichierPDF'] = $request->file('fichierPDF')->store('service', 'public');
        } else {
            unset($validated['fichierPDF']);
        }

        $service->update($validated);

        return redirect()->route('parametre.service.create')
            ->with('success', 'Modifié avec succès');
    }

    /* ---------------------------------------------------------------- */
    /* DESTROY                                                           */
    /* ---------------------------------------------------------------- */
    public function destroy(Request $request, Service $service)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = CodeAcces::where('id', 1)
            ->where('code', $request->code)
            ->first();

        if (! $code) {
            return response()->json([
                'success' => false,
                'message' => "Code d'accès est invalide. Contacter l'administrateur !",
            ], 403);
        }

        // Supprimer le PDF principal
        if ($service->fichierPDF && Storage::disk('public')->exists($service->fichierPDF)) {
            Storage::disk('public')->delete($service->fichierPDF);
        }

        // Supprimer le document afférent (si applicable)
        if (! empty($service->txt_documentsAfferants)
            && Storage::disk('public')->exists($service->txt_documentsAfferants)) {
            Storage::disk('public')->delete($service->txt_documentsAfferants);
        }

        $nom = $service->txt_numeroNote;
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => "Enregistrement \"$nom\" supprimé avec succès.",
        ]);
    }
}