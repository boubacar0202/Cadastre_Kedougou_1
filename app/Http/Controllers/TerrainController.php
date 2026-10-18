<?php

namespace App\Http\Controllers;

use App\Models\Shapefile;
use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CodeAcces;  
use Inertia\Inertia;     

class TerrainController extends Controller
{
    public function destroyTerrain(Request $request, $id)
    {
        try {
            // 🔐 Vérification du code administrateur
            if (!$request->filled('code')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code administrateur requis.'
                ], 422);
            }

            $code = CodeAcces::where('id', 3)
                ->where('code', $request->input('code'))
                ->first();

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code d\'accès invalide.'
                ], 403);
            }

            DB::beginTransaction();

            $terrain = Terrain::findOrFail($id);
            $titulaire = $terrain->titulaire;

            // ✅ Suppression de la référence cadastrale (relation directe)
            if ($terrain->referenceCadastrale) {
                $terrain->referenceCadastrale->delete();
            }

            // ✅ Suppression du dossier (relation directe)
            if ($terrain->dossier) {
                $terrain->dossier->delete();
            }

            // ✅ Suppression du terrain
            $terrain->delete();

            // ✅ Suppression du titulaire s'il n'a plus de terrains
            if ($titulaire && $titulaire->terrains()->count() === 0) {
                $titulaire->delete();
            }

            DB::commit();

            return redirect()->route('donnee.create')->with([
                'success' => true,
                'message' => 'Terrain supprimé avec succès.'
            ]);
  
        } catch (\Throwable $e) {
            DB::rollBack();

            
            return redirect()->route('donnee.create')->with([
                'success' => false,
                'message' => 'Erreur serveur lors de la suppression.',
                'error' => $e->getMessage() // utile en dev
            ], 500);
 

        }
    }

    public function searchTerrain(Request $request)
    {
        $parcelle = trim($request->parcelle);
        $section = trim($request->section);

        $terrain = Terrain::whereRaw("RIGHT(txt_nicad, 5) = ?", [$parcelle])
            ->whereRaw('TRIM(txt_num_section) = ?', [$section])
            ->first();

        return response()->json([
            'txt_num_dossier' => $terrain?->dossier?->txt_num_dossier,
            'txt_nicad' => $terrain?->txt_nicad,
            'nbr_surface' => $terrain?->nbr_surface,
            'txt_num_lotissement' => $terrain?->txt_num_lotissement,
            'txt_lotissement' => $terrain?->txt_lotissement,
        ]);
    }

    public function consulterParcelles(Request $request)
    {
        $payload = $request->validate([
            'parcelles' => 'required|array|min:1|max:500',
            'parcelles.*.section' => 'required|string|max:12',
            'parcelles.*.parcelle' => 'nullable|string|max:12',
            'parcelles.*.lotissement' => 'nullable|string|max:255',
        ]);

        $demandes = collect($payload['parcelles'])->map(function ($item) {
            return [
                'section' => $this->numeroCadastre($item['section'] ?? '', 3),
                'parcelle' => $this->numeroCadastre($item['parcelle'] ?? '', 5),
                'lotissement' => trim((string) ($item['lotissement'] ?? '')),
            ];
        });

        $sections = $demandes->pluck('section')->filter()->unique()->values();
        $variantes = $sections->flatMap(function ($section) {
            $court = ltrim($section, '0');
            return [$section, $court === '' ? '0' : $court];
        })->unique()->values();
        $terrains = Terrain::with(['dossier', 'commune', 'titulaire'])
            ->where(function ($requete) use ($variantes) {
                $requete->whereIn('txt_num_section', $variantes)
                    ->orWhereNotNull('txt_nicad');
            })
            ->get();
        $nicadsEnregistres = $this->nicadsParcellesEnregistrees();

        $resultats = $demandes->map(function ($demande) use ($terrains, $nicadsEnregistres) {
            $section = $demande['section'];
            $deLaSection = $terrains->filter(function ($terrain) use ($section) {
                $partie = $this->partieNicad($terrain->txt_nicad);
                if ($partie && $partie['section'] === $section) {
                    return true;
                }

                return $this->numeroCadastre($terrain->txt_num_section, 3) === $section;
            });
            $serie = $deLaSection
                ->map(function ($terrain) use ($section) {
                    $partie = $this->partieNicad($terrain->txt_nicad);
                    if (!$partie || $partie['section'] !== $section || (int) $partie['parcelle'] === 0) {
                        return null;
                    }
                    $partie['lotissement'] = (string) $terrain->txt_lotissement;

                    return $partie;
                })
                ->filter()
                ->concat($nicadsEnregistres->filter(
                    fn ($partie) => $partie['section'] === $section && (int) $partie['parcelle'] > 0
                ))
                ->values();
            $code = $this->codeDeLaSerie($serie, $demande['lotissement'])
                ?: $this->codeCommune($deLaSection->first(function ($terrain) use ($demande) {
                    return $terrain->commune?->txt_codeCommune
                        && $this->memeLotissement($terrain->txt_lotissement, $demande['lotissement']);
                })?->commune?->txt_codeCommune
                    ?? $deLaSection->first(fn ($terrain) => $terrain->commune?->txt_codeCommune)?->commune?->txt_codeCommune);
            $serieDuCode = $code === ''
                ? $serie
                : $serie->filter(fn ($partie) => $partie['code'] === $code)->values();
            if ($serieDuCode->isEmpty()) {
                $serieDuCode = $serie;
            }
            $dernierNicad = $serieDuCode->sortBy(fn ($partie) => (int) $partie['parcelle'])->last();
            $dernier = $dernierNicad ? (int) $dernierNicad['parcelle'] : 0;
            $terrain = $demande['parcelle']
                ? $deLaSection->first(function ($candidat) use ($section, $demande) {
                    $parcelle = $this->numeroCadastre($candidat->txt_num_parcelle, 5);
                    $partie = $this->partieNicad($candidat->txt_nicad);
                    $nicad = (string) $candidat->txt_nicad;
                    $numeroCorrespond = ($partie && $partie['section'] === $section && $partie['parcelle'] === $demande['parcelle'])
                        || $parcelle === $demande['parcelle']
                        || str_ends_with($nicad, $demande['parcelle'])
                        || str_ends_with($nicad, $section . $demande['parcelle']);

                    return $numeroCorrespond
                        && $this->memeLotissement($candidat->txt_lotissement, $demande['lotissement']);
                })
                : null;

            $suffixe = $section . $demande['parcelle'];
            $titulaire = $terrain?->titulaire;
            $nomTitulaire = trim(implode(' ', array_filter([
                $titulaire?->txt_prenom,
                $titulaire?->txt_nom,
                $titulaire?->txt_denominationPM,
                $titulaire?->txt_personneResponsablePA,
            ])));

            return [
                'section' => $section,
                'parcelle' => $demande['parcelle'],
                'lotissement' => $demande['lotissement'],
                'suffixe' => $demande['parcelle'] ? $suffixe : '',
                'code_commune' => $code,
                'nicad_calcule' => $code && $demande['parcelle'] ? $code . $suffixe : '',
                'dernier_numero' => $dernier,
                'dernier_nicad' => $dernierNicad['nicad'] ?? ($code && $dernier ? $code . $section . str_pad((string) $dernier, 5, '0', STR_PAD_LEFT) : ''),
                'terrain' => $terrain ? [
                    'id' => $terrain->id,
                    'txt_nicad' => $terrain->txt_nicad,
                    'txt_num_section' => $terrain->txt_num_section,
                    'txt_num_parcelle' => $terrain->txt_num_parcelle,
                    'txt_lotissement' => $terrain->txt_lotissement,
                    'txt_num_lotissement' => $terrain->txt_num_lotissement,
                    'nbr_surface' => $terrain->nbr_surface,
                    'txt_num_titre' => $terrain->txt_num_titre,
                    'commune' => $terrain->commune?->slt_commune,
                    'titulaire' => $nomTitulaire,
                ] : null,
                'dossier' => $terrain?->dossier ? [
                    'txt_num_dossier' => $terrain->dossier->txt_num_dossier,
                    'txt_num_dordre' => $terrain->dossier->txt_num_dordre,
                    'slt_service_rendu' => $terrain->dossier->slt_service_rendu,
                    'txt_etat_cession' => $terrain->dossier->txt_etat_cession,
                    'txt_cession_definitive' => $terrain->dossier->txt_cession_definitive,
                    'dt_date_creation' => optional($terrain->dossier->dt_date_creation)->format('d/m/Y'),
                ] : null,
            ];
        })->values();

        return response()->json(['resultats' => $resultats]);
    }

    private function nicadsParcellesEnregistrees()
    {
        return Shapefile::query()
            ->whereNotNull('verification')
            ->get(['verification'])
            ->map(function ($shapefile) {
                $verification = $shapefile->verification;
                if (!is_array($verification) || ($verification['type'] ?? '') !== 'parcelle') {
                    return null;
                }

                $partie = $this->partieNicad($verification['nicad'] ?? '');
                if (!$partie) {
                    $section = $this->numeroCadastre($verification['section'] ?? '', 3);
                    $parcelle = $this->numeroCadastre($verification['prochain'] ?? '', 5);
                    $code = $this->codeCommune($verification['codeCommune'] ?? '');
                    if ($section === '' || $parcelle === '' || $code === '') {
                        return null;
                    }
                    $partie = [
                        'code' => $code,
                        'section' => $section,
                        'parcelle' => $parcelle,
                        'nicad' => $code . $section . $parcelle,
                    ];
                }
                $partie['lotissement'] = (string) ($verification['lotissement'] ?? '');

                return $partie;
            })
            ->filter()
            ->values();
    }

    private function codeDeLaSerie($serie, string $lotissement): string
    {
        $retenue = $lotissement !== ''
            ? $serie->filter(fn ($partie) => $this->memeLotissement($partie['lotissement'] ?? '', $lotissement))
            : collect();
        if ($retenue->isEmpty()) {
            $retenue = $serie;
        }

        return (string) ($retenue
            ->countBy('code')
            ->sortDesc()
            ->keys()
            ->first() ?? '');
    }

    private function codeCommune(?string $valeur): string
    {
        $chiffres = preg_replace('/\D/', '', (string) $valeur);

        return strlen($chiffres) >= 8 ? substr($chiffres, 0, 8) : '';
    }

    private function partieNicad(?string $nicad): ?array
    {
        $chiffres = preg_replace('/\D/', '', (string) $nicad);
        if (strlen($chiffres) < 11) {
            return null;
        }

        $code = substr($chiffres, 0, 8);
        $reste = substr($chiffres, 8);
        if (strlen($reste) < 4) {
            return null;
        }

        $section = substr($reste, 0, 3);
        $parcelle = str_pad(substr($reste, 3, 5), 5, '0', STR_PAD_LEFT);

        return [
            'code' => $code,
            'section' => $section,
            'parcelle' => $parcelle,
            'nicad' => $code . $section . $parcelle,
        ];
    }

    private function memeLotissement(?string $enregistre, ?string $demande): bool
    {
        $nomDemande = $this->normaliserNom($demande);
        if ($nomDemande === '') {
            return true;
        }

        return $this->normaliserNom($enregistre) === $nomDemande;
    }

    private function normaliserNom(?string $valeur): string
    {
        $valeur = trim(preg_replace('/\s+/u', ' ', (string) $valeur));
        $valeur = strtr(mb_strtolower($valeur), [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'œ' => 'oe', 'æ' => 'ae',
        ]);

        return $valeur;
    }

    private function numeroCadastre(?string $valeur, int $longueur): string
    {
        $chiffres = preg_replace('/\D/', '', (string) $valeur);
        if ($chiffres === '') {
            return '';
        }

        return str_pad(substr($chiffres, -$longueur), $longueur, '0', STR_PAD_LEFT);
    }
}