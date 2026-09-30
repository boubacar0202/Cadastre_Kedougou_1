<?php 
 
namespace App\Http\Controllers;

use App\Models\Terrain;
use App\Models\Dossier;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DonneeController extends Controller
{
    public function create(Request $request)
    {
        // Relations nécessaires pour l'affichage
        $relations = [
            'region',
            'departement',
            'commune',
            'arrondissement',
            'dossier',
            'titulaire',
            'references_cadastrales',
            'evaluations_terrains',
            'evaluations_batis',
            'evaluations_clotures',
            'evaluations_amenagements',
            'evaluations_cours_amenagees'
        ];

        $query = Terrain::with($relations);

        // Filtres
        if ($request->filled('mois')) {
            $query->whereHas('dossier', function($q) use ($request) {
                $q->whereMonth('dt_date_creation', $request->mois);
            });
        }

        if ($request->filled('annee')) {
            $query->whereHas('dossier', function($q) use ($request) {
                $q->whereYear('dt_date_creation', $request->annee);
            });
        }

        if ($request->filled('numero')) {
            $query->whereHas('dossier', function($q) use ($request) {
                $q->where('txt_num_dossier', 'like', '%' . $request->numero . '%');
            });
        }

        // ✅ Recherche améliorée sur prenomNom
        if ($request->filled('prenomNom')) {
            $searchTerm = $request->prenomNom;
            
            $query->whereHas('titulaire', function($q) use ($searchTerm) {
                // Nettoyer et échapper les caractères spéciaux LIKE
                $searchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
                
                $terms = array_filter(explode(' ', $searchTerm));
                
                if (count($terms) > 1) {
                    // Recherche multi-mots (plus précise)
                    foreach ($terms as $term) {
                        $term = '%' . $term . '%';
                        $q->where(function($subQ) use ($term) {
                            $subQ->where('txt_prenom', 'like', $term)
                                ->orWhere('txt_nom', 'like', $term)
                                ->orWhere('txt_denominationPM', 'like', $term);
                        });
                    }
                } else {
                    // Recherche simple
                    $term = '%' . $searchTerm . '%';
                    $q->where(function($subQ) use ($term) {
                        $subQ->where('txt_prenom', 'like', $term)
                            ->orWhere('txt_nom', 'like', $term)
                            ->orWhere('txt_denominationPM', 'like', $term)
                            // Recherche sur le nom complet
                            ->orWhere(DB::raw("CONCAT_WS(' ', txt_prenom, txt_nom)"), 'like', $term);
                    });
                }
            });
        }

        if ($request->filled('refServiceRendu')) {
            $query->whereHas('dossier', function($q) use ($request) {
                $q->where('slt_service_rendu', $request->refServiceRendu);
            });
        }
        // Pagination
        $terrains = $query->orderBy('id', 'desc')->paginate(15);
        

        // 🔥 TOTAL GÉNÉRAL DE LA BASE (sans filtres)
        $totalGeneral = Terrain::count();

        // Récupération des Sans Nicad  sans filtres
        $terrainsSansNicad = Terrain::where(function($q) {
            $q->whereNull('txt_nicad')
              ->orWhere('txt_nicad', '')
              ->orWhere('txt_nicad', '');
        })->count();

        // Récupération des services uniques
        $uniqueServices = Cache::remember('unique_services_donnee', 3600, function () {
            return Dossier::whereNotNull('slt_service_rendu')
                ->distinct()
                ->pluck('slt_service_rendu')
                ->toArray();
        });

        return Inertia::render('donnee/create', [
            'terrains' => $terrains,
            'uniqueServices' => $uniqueServices,
            'totalGeneral' => $totalGeneral, 
            'terrainsSansNicad' => $terrainsSansNicad,
            'filters' => $request->only(['mois', 'annee', 'numero', 'prenomNom', 'refServiceRendu'])
        ]);
    }

   
}