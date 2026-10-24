<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $utilisateur = $request->user();

        if ($utilisateur->estProtege() && $request->validated('email') !== $utilisateur->email) {
            return Redirect::route('profile.edit')->withErrors([
                'email' => 'L’adresse de ce compte protégé ne peut pas être modifiée.',
            ]);
        }

        $utilisateur->fill($request->validated());

        if ($utilisateur->isDirty('email')) {
            $utilisateur->email_verified_at = null;
        }

        $utilisateur->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->estProtege()) {
            return Redirect::route('profile.edit')->withErrors([
                'password' => 'Ce compte est protégé et ne peut pas être supprimé.',
            ]);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
