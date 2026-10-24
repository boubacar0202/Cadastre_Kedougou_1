<?php

namespace App\Http\Controllers;

use App\Models\BaremeEvaluation;
use App\Models\CodeAcces;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BaremeEvaluationController extends Controller
{
    public function index()
    {
        return Inertia::render('parametre/bareme/index', [
            'baremes' => BaremeEvaluation::query()
                ->orderBy('groupe')
                ->orderBy('type')
                ->orderBy('ordre')
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        BaremeEvaluation::create($this->valider($request));

        return redirect()
            ->route('parametre.bareme.index')
            ->with('success', 'Barème enregistré.');
    }

    public function update(Request $request, BaremeEvaluation $bareme)
    {
        $bareme->update($this->valider($request, $bareme));

        return redirect()
            ->route('parametre.bareme.index')
            ->with('success', 'Barème modifié.');
    }

    public function destroy(Request $request, BaremeEvaluation $bareme)
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

        $bareme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barème supprimé.',
        ]);
    }

    private function valider(Request $request, ?BaremeEvaluation $bareme = null): array
    {
        $groupe = $request->input('groupe');
        $type = in_array($groupe, ['batiment', 'taux_locatif'], true)
            ? trim((string) $request->input('type', ''))
            : '';

        $request->merge([
            'type' => $type === '' ? null : $type,
            'code' => trim((string) $request->input('code')),
        ]);

        $unique = Rule::unique('baremes_evaluation')
            ->where(fn ($query) => $query->where('groupe', $groupe)->where('type', $type));

        if ($bareme) {
            $unique->ignore($bareme->id);
        }

        $validated = $request->validate([
            'groupe' => ['required', Rule::in(['batiment', 'secteur', 'cours_amenagee', 'cloture', 'coefficient', 'taux_locatif'])],
            'type' => [
                'nullable',
                Rule::requiredIf(in_array($groupe, ['batiment', 'taux_locatif'], true)),
                Rule::in(['Maison individuelle', 'Immeuble collectif']),
            ],
            'code' => ['required', 'string', 'max:20', $unique],
            'prix' => ['required', 'numeric', 'min:0'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:999'],
            'actif' => ['required', 'boolean'],
        ], [
            'groupe.required' => 'Le groupe est requis.',
            'type.required' => 'Le type de bâtiment est requis.',
            'code.required' => 'Le code est requis.',
            'code.unique' => 'Ce code existe déjà pour ce groupe.',
            'prix.required' => 'Le prix est requis.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
        ]);

        $validated['type'] = $type;
        $validated['ordre'] = $validated['ordre'] ?? ($bareme->ordre ?? 0);
        $validated['actif'] = $request->boolean('actif');

        return $validated;
    }
}
