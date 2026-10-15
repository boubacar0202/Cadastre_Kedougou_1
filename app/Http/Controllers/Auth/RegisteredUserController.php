<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'role' => User::normalizeRole($request->input('role')),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:admin,user',
        ], [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => "L'adresse e-mail est obligatoire.",
            'email.email' => "L'adresse e-mail n'est pas valide.",
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle choisi est invalide.',
        ]);

        // Le premier compte, s'il est administrateur, n'a personne pour le valider.
        $autoValidate = $request->role === 'admin' && ! User::query()->exists();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_validated' => $autoValidate,
        ]);

        event(new Registered($user));

        if ($autoValidate) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()
                ->route('dashboard')
                ->with('info', $user->name . ', bienvenue sur le Système !');
        }

        return redirect()
            ->route('login')
            ->with('status', 'Votre compte a été créé. Un administrateur doit le valider avant que vous puissiez vous connecter.');
    }
}
