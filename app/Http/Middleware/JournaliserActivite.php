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
            ActiviteUtilisateur::create([
                'user_id' => $userId,
                'action' => $action,
            ]);
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
}
