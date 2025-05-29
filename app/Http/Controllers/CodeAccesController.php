<?php

namespace App\Http\Controllers;

use App\Models\CodeAcces;
use Illuminate\Http\Request;

class CodeAccesController extends Controller
{
    //
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = CodeAcces::where('code', $request->code)->first();

        if ($code) {
            // ✅ Marquer comme utilisé
            $code->utilise = true;
            $code->save();

            return response()->json([
                'exists' => true,
                'success' => 'Code confirmé.'
            ]);
        }

        return response()->json([
            'exists' => false,
            'error' => 'Code invalide.'
        ]);
    }
}
