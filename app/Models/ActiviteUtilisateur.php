<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiviteUtilisateur extends Model
{
    protected $table = 'activites_utilisateurs';

    protected $fillable = [
        'user_id',
        'action',
        'methode',
        'module',
        'chemin',
        'libelle',
        'reference',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
