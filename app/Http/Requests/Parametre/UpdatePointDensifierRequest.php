<?php

namespace App\Http\Requests\Parametre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePointDensifierRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Gérer l'autorisation selon vos besoins
    }

    public function rules()
    {
        return [
            'txt_nomPoint'              => 'required|string|max:255',
            'txt_dateCreationPoint'     => 'nullable|date',
            'slt_departement'           => 'required|exists:departements,id',
            'slt_arrondissement'        => 'required|exists:arrondissements,id',
            'slt_commune'               => 'required|exists:communes,id',
            'txt_descriptionPoint'      => 'nullable|string',
            'txt_situationPoint'        => 'nullable|string',
            'txt_estPoint'              => 'nullable|numeric',
            'txt_nordPoint'             => 'nullable|numeric',
            'txt_hauteurPoint'          => 'nullable|numeric',
            'fichierPDFPoint' => 'nullable|file|mimes:pdf|max:2048', // 2 Mo max
        ];
    }
}