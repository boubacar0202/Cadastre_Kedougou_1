<?php

namespace App\Http\Middleware;

use App\Models\ActiviteUtilisateur;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class JournaliserActivite
{
    private static ?bool $tableReady = null;

    private static ?bool $detailReady = null;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $userId = $request->user()?->id;
        if (! $userId || $response->getStatusCode() >= 400) {
            return $response;
        }

        $action = $this->actionFor($request);
        if (! $action || ! $this->tableReady()) {
            return $response;
        }

        try {
            $payload = [
                'user_id' => $userId,
                'action' => $action,
            ];

            if ($this->detailReady()) {
                $path = trim($request->path(), '/');
                $module = $this->moduleFor($path);
                $payload['methode'] = $request->method();
                $payload['chemin'] = mb_substr($path, 0, 255);
                $payload['module'] = $module;
                $payload['libelle'] = $this->libelleFor($action, $module);
                $payload['reference'] = $this->referenceFor($request);

                if ($action === 'recherche' && $this->poursuivreRechercheEnCours($userId, $payload)) {
                    return $response;
                }
            }

            ActiviteUtilisateur::create($payload);
        } catch (\Throwable) {
            // Le journal ne doit pas interrompre le traitement.
        }

        return $response;
    }

    private function actionFor(Request $request): ?string
    {
        $path = trim($request->path(), '/');

        if ($this->isIgnored($path)) {
            return null;
        }

        $method = $request->method();

        if (in_array($method, ['PUT', 'PATCH'], true)) {
            return 'modification';
        }

        if ($method === 'POST' && $this->isSearchPath($path)) {
            return 'recherche';
        }

        if ($method === 'POST') {
            return 'enregistrement';
        }

        if ($method === 'GET' && $this->hasSearch($request)) {
            return 'recherche';
        }

        return null;
    }

    private function isIgnored(string $path): bool
    {
        $ignored = [
            'login',
            'logout',
            'register',
            'forgot-password',
            'reset-password',
            'sanctum/csrf-cookie',
            'api/chart-data',
            'api/stats',
            'api/cession-data',
            'api/evaluation-utilisateur',
            'messages/unread-total',
            'messages/unread-by-user',
        ];

        foreach ($ignored as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/') || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return str_starts_with($path, 'livewire')
            || str_starts_with($path, 'message')
            || str_starts_with($path, 'verifier-code')
            || str_starts_with($path, 'code/verify');
    }

    private function isSearchPath(string $path): bool
    {
        return (bool) preg_match('/recherch|search|fetch-reference|verification|verify/i', $path);
    }

    private function hasSearch(Request $request): bool
    {
        foreach (['numero', 'prenomNom', 'refServiceRendu', 'q', 'search', 'reference', 'query', 'nicad', 'txt_reference', 'nom'] as $key) {
            $value = $request->query($key);
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function tableReady(): bool
    {
        if (self::$tableReady !== true) {
            self::$tableReady = Schema::hasTable('activites_utilisateurs');
        }

        return self::$tableReady === true;
    }

    private function detailReady(): bool
    {
        if (self::$detailReady !== true) {
            self::$detailReady = Schema::hasColumn('activites_utilisateurs', 'libelle');
        }

        return self::$detailReady === true;
    }

    private function moduleFor(string $path): string
    {
        $rules = [
            'secretariat' => 'Secrétariat',
            'geometre' => 'Géomètre',
            'matriceCadastrale' => 'Matrice cadastrale',
            'mutation' => 'Mutation',
            'shapefile' => 'Shapefile',
            'generer-polygone' => 'Plan',
            'generer-angles' => 'Plan',
            'pointDensifier' => 'Point densifié',
            'lotissement' => 'Lotissement',
            'instancearrivee' => 'Instance arrivée',
            'instancedepart' => 'Instance départ',
            'arrivee' => 'Arrivée',
            'depart' => 'Départ',
            'instance' => 'Instance',
            'parcelle' => 'Plan',
            'plan' => 'Plan',
            'terrain' => 'Terrain',
            'donnee' => 'Données',
            'bareme' => 'Barème',
            'service' => 'Service',
            'profile' => 'Profil',
        ];

        foreach ($rules as $needle => $label) {
            if (str_contains($path, $needle)) {
                return $label;
            }
        }

        $segment = explode('/', $path)[0] ?? '';

        return $segment !== '' ? ucfirst($segment) : 'Application';
    }

    private function libelleFor(string $action, string $module): string
    {
        $labels = [
            'enregistrement' => 'Enregistrement',
            'modification' => 'Modification',
            'recherche' => 'Recherche',
        ];

        return ($labels[$action] ?? 'Traitement').' — '.$module;
    }

    private function referenceFor(Request $request): ?string
    {
        foreach ([
            'txt_nicad',
            'nicad',
            'txt_nicad_mutation',
            'txt_num_dossier',
            'numero',
            'txt_reference',
            'refServiceRendu',
            'txt_num_parcelle',
            'txt_prenom',
            'txt_nom',
            
        ] as $key) {
            $value = $request->input($key);
            if (is_string($value) && trim($value) !== '') {
                return mb_substr(trim($value), 0, 120);
            }
            if (is_numeric($value)) {
                return mb_substr((string) $value, 0, 120);
            }
        }

        return null;
    }

    /**
     * Une saisie qui prolonge la précédente (boub, bouba, boubacar)
     * met à jour la même recherche au lieu d'en créer une nouvelle.
     */
    private function poursuivreRechercheEnCours(int $userId, array $payload): bool
    {
        $precedente = ActiviteUtilisateur::query()
            ->where('user_id', $userId)
            ->where('action', 'recherche')
            ->where('chemin', $payload['chemin'])
            ->where('updated_at', '>=', now()->subSeconds(20))
            ->latest('id')
            ->first();

        if (! $precedente || ! $this->memeSaisie($precedente->reference, $payload['reference'] ?? null)) {
            return false;
        }

        $precedente->update([
            'methode' => $payload['methode'],
            'reference' => $payload['reference'],
        ]);

        return true;
    }

    private function memeSaisie(?string $precedente, ?string $actuelle): bool
    {
        $precedente = trim((string) $precedente);
        $actuelle = trim((string) $actuelle);

        if ($precedente === '' || $actuelle === '') {
            return false;
        }

        return mb_stripos($actuelle, $precedente) === 0
            || mb_stripos($precedente, $actuelle) === 0;
    }
}
