<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class CodeAcces extends Model
{
    protected $table = 'code_acces';

    // Colonnes que l'on peut remplir via mass-assignment
    protected $fillable = [
        'code',
        'utilise',
    ];

    // Si tu veux que 'utilise' soit un boolean automatiquement
    protected $casts = [
        'utilise' => 'boolean',
    ];

    /**
     * Vérifie un code sans le laisser lisible en base et limite les essais.
     */
    public static function confirme(?int $id, string $saisi): bool
    {
        $saisi = trim($saisi);
        if ($saisi === '') {
            return false;
        }

        $cle = 'code-acces|'.(Auth::id() ?: 'invite').'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            abort(429, 'Trop de tentatives. Réessayez dans une minute.');
        }

        $codes = $id === null
            ? static::query()->get()
            : static::query()->whereKey($id)->get();

        $ligne = $codes->first(function (self $code) use ($saisi) {
            if (Hash::isHashed($code->code)) {
                return Hash::check($saisi, $code->code);
            }

            return hash_equals((string) $code->code, $saisi);
        });

        if (! $ligne) {
            RateLimiter::hit($cle, 60);

            return false;
        }

        if (! Hash::isHashed($ligne->code)) {
            $ligne->code = Hash::make($saisi);
            $ligne->save();
        }

        RateLimiter::clear($cle);

        return true;
    }
}
