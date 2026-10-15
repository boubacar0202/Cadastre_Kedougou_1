<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                abort(403, 'Accès réservé aux administrateurs.');
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
