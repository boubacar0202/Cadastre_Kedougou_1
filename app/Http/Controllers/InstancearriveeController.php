<?php

namespace App\Http\Controllers;

use App\Models\Arrivee;
use App\Models\Depart;
use Inertia\Inertia;
use Illuminate\Http\Request;

class InstancearriveeController extends Controller
{
    //
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return Inertia::render('');
    }
 
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $arrivees = Arrivee::orderBy('id', 'desc')->get();
        $departs = Depart::orderBy('id', 'desc')->get();

        return Inertia::render('instancearrivee/create', [
            'arrivees' => $arrivees,
            'departs' => $departs,
        ]);
    }

    public function destroy($id)
    {
        $arrivee = Arrivee::findOrFail($id);
        $arrivee->delete();

        return redirect()->back()->with('success', 'Courrier Arrivée N° ' . $arrivee->txt_numdordre . ' supprimé avec succès.');
    }

 
}
