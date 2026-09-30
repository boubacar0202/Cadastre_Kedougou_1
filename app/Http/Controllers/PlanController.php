<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\PointDensifier;
use App\Models\Shapefile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Page principale avec le plan
     */
    public function index()
    {
        return Inertia::render('plan/create', [
            'point_densifiers' => PointDensifier::with([
                'departement', 'arrondissement', 'commune'
            ])->get(),
            'shapefiles' => Shapefile::latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $points = PointDensifier::with(['departement', 'arrondissement', 'commune'])->get();
        $departements = Departement::all();

        return Inertia::render('plan/create', [
            'point_densifiers' => $points,
            'departements'     => $departements,
            'arrondissements'  => [],
            'communes'         => [],
            'shapefiles'       => Shapefile::latest()->get(),
        ]);
    }

    /**
     * Liste JSON des shapefiles (pour axios)
     */
    // public function list()
    // {
    //     return response()->json(Shapefile::latest()->get());
    // }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     // 1. Validation
    //     $request->validate([
    //         'nom'  => 'required|string|max:255',
    //         'shp'  => 'required|file',
    //         'shx'  => 'required|file',
    //         'dbf'  => 'required|file',
    //         'prj'  => 'nullable|file',
    //         'srid' => 'nullable|string|max:10',
    //     ], [
    //         'nom.required' => 'Le nom du plan est requis.',
    //         'shp.required' => 'Le fichier .shp est requis.',
    //         'shx.required' => 'Le fichier .shx est requis.',
    //         'dbf.required' => 'Le fichier .dbf est requis.',
    //     ]);

    //     // 2. Vérification extensions
    //     foreach (['shp' => 'shp', 'shx' => 'shx', 'dbf' => 'dbf', 'prj' => 'prj'] as $field => $ext) {
    //         if ($request->hasFile($field)) {
    //             $actual = strtolower($request->file($field)->getClientOriginalExtension());
    //             if ($actual !== $ext) {
    //                 return back()->withErrors([$field => "Le fichier doit avoir l'extension .{$ext}"])->withInput();
    //             }
    //         }
    //     }

    //     // 3. Dossier temporaire
    //     $folder  = 'tmp_shp_' . Str::uuid();
    //     $tmpPath = storage_path("app/{$folder}");
    //     if (!is_dir($tmpPath)) mkdir($tmpPath, 0775, true);

    //     foreach (['shp', 'shx', 'dbf', 'prj'] as $ext) {
    //         if ($request->hasFile($ext)) {
    //             $request->file($ext)->move($tmpPath, "shape.{$ext}");
    //         }
    //     }

    //     $shpFile = "{$tmpPath}/shape.shp";
    //     $srid    = $request->input('srid', '4326');

    //     // 4. Lire le shapefile
    //     try {
    //         $reader = new \Shapefile\ShapefileReader($shpFile);
    //         $features = [];

    //         while ($record = $reader->fetchRecord()) {
    //             if ($record->isDeleted()) continue;

    //             $geomJson = $record->getGeoJSON();
    //             $geom = json_decode($geomJson, true);

    //             if (!$geom || !isset($geom['type'])) continue;

    //             // ✅ CORRECTION 1 : Nettoyer le type (PolygonM → Polygon)
    //             $geom['type'] = preg_replace('/(M|Z|ZM)$/i', '', $geom['type']);

    //             // ✅ CORRECTION 2 : Nettoyer + reprojeter les coordonnées
    //             $geom['coordinates'] = $this->cleanCoordinates($geom['coordinates']);

    //             // ✅ CORRECTION 3 : Supprimer la bbox (obsolète après reprojection)
    //             unset($geom['bbox']);

    //             $features[] = [
    //                 'type'       => 'Feature',
    //                 'geometry'   => $geom,
    //                 'properties' => $record->getDataArray(),
    //             ];
    //         }

    //         \Log::info("Shapefile importé : {$shpFile}, " . count($features) . " features, " . $srid);
    //     } catch (\Exception $e) {
    //         array_map('unlink', glob("{$tmpPath}/*"));
    //         @rmdir($tmpPath);
    //         \Log::error('Erreur lecture shapefile : ' . $e->getMessage());
    //         return back()->withErrors(['shp' => 'Erreur lecture : ' . $e->getMessage()]);
    //     }

    //     // 5. GeoJSON final
    //     $geojson = [
    //         'type'     => 'FeatureCollection',
    //         'features' => $features,
    //     ];

    //     $nbEntites = count($features);
    //     $bbox      = $this->computeBbox($features);

    //     // 6. Stocker le fichier
    //     $nomTable  = 'shp_' . Str::lower(Str::random(10));
    //     $finalPath = "shapefiles/{$nomTable}.geojson";
    //     Storage::disk('public')->put($finalPath, json_encode($geojson));

    //     // 7. Nettoyer le dossier temporaire
    //     array_map('unlink', glob("{$tmpPath}/*"));
    //     @rmdir($tmpPath);

    //     // 8. Enregistrer en base
    //     $shapefile = Shapefile::create([
    //         'nom'        => $request->nom,
    //         'nom_table'  => $nomTable,
    //         'srid'       => '4326',
    //         'nb_entites' => $nbEntites,
    //         'bbox'       => $bbox,
    //         'couleur'    => '#3388ff',
    //         'visible'    => true,
    //         'user_id'    => auth()->id(),
    //     ]);

    //     return back()->with([
    //         'success' => "✅ Shapefile #{$shapefile->id} importé avec succès ({$nbEntites} entités)",
    //     ]);
    // }

    /**
     * Renvoie le GeoJSON d'un shapefile
     */
    // public function geojson(Shapefile $shapefile)
    // {
    //     $path = storage_path("app/public/shapefiles/{$shapefile->nom_table}.geojson");

    //     if (!file_exists($path)) {
    //         return response()->json([
    //             'error'     => 'Fichier introuvable',
    //             'path'      => $path,
    //             'nom_table' => $shapefile->nom_table,
    //         ], 404);
    //     }

    //     return response(file_get_contents($path), 200)
    //         ->header('Content-Type', 'application/json');
    // }

    /**
     * Mise à jour d'un shapefile
     */
    // public function update(Request $request, Shapefile $shapefile)
    // {
    //     $validated = $request->validate([
    //         'nom'     => 'sometimes|string|max:255',
    //         'couleur' => 'sometimes|string|max:20',
    //         'visible' => 'sometimes|boolean',
    //     ]);

    //     $shapefile->update($validated);
    //     return response()->json($shapefile);
    // }

    /**
     * Suppression d'un shapefile
     */
    // public function destroy(Shapefile $shapefile)
    // {
    //     $path = "shapefiles/{$shapefile->nom_table}.geojson";
    //     Storage::disk('public')->delete($path);
    //     $shapefile->delete();

    //     return back()->with('success', '✅ Shapefile supprimé');
    // }

    /**
     * Nettoie les coordonnées :
     * - Enlève Z et M (garde seulement [x, y])
     * - Reprojette UTM 28N → WGS84 automatiquement
     */
    // private function cleanCoordinates($coords)
    // {
    //     if (!is_array($coords)) return $coords;

    //     // Cas terminal : [x, y] ou [x, y, z] ou [x, y, z, m]
    //     if (isset($coords[0]) && is_numeric($coords[0])) {
    //         $x = (float) $coords[0];
    //         $y = (float) $coords[1];

    //         // ✅ Détection UTM : x > 180 OU y > 90
    //         if (abs($x) > 180 || abs($y) > 90) {
    //             [$x, $y] = $this->utm28nToWgs84($x, $y);
    //         }

    //         return [round($x, 8), round($y, 8)]; // 2 valeurs seulement
    //     }

    //     // Cas récursif
    //     return array_map(fn($c) => $this->cleanCoordinates($c), $coords);
    // }

    /**
     * Reprojection UTM 28N → WGS84 via proj4php
     */
    // private function utm28nToWgs84($easting, $northing)
    // {
    //     try {
    //         static $proj4 = null;
    //         static $utm28N = null;
    //         static $wgs84 = null;

    //         if ($proj4 === null) {
    //             $proj4  = new \proj4php\Proj4php();
    //             $utm28N = new \proj4php\Proj('+proj=utm +zone=28 +datum=WGS84 +units=m +no_defs', $proj4);
    //             $wgs84  = new \proj4php\Proj('+proj=longlat +datum=WGS84 +no_defs', $proj4);
    //         }

    //         $point  = new \proj4php\Point($easting, $northing);
    //         $result = $proj4->transform($utm28N, $wgs84, $point);

    //         return [$result->getX(), $result->getY()];
    //     } catch (\Exception $e) {
    //         \Log::error("Erreur reprojection UTM→WGS84 : {$e->getMessage()} pour ({$easting}, {$northing})");
    //         return [$easting, $northing]; // Fallback
    //     }
    // }

    /**
     * Calcule la bounding box d'un ensemble de features GeoJSON
     */
    // private function computeBbox(array $features): ?array
    // {
    //     $minX = $minY = PHP_FLOAT_MAX;
    //     $maxX = $maxY = -PHP_FLOAT_MAX;

    //     $walk = function ($coords) use (&$walk, &$minX, &$minY, &$maxX, &$maxY) {
    //         if (!is_array($coords)) return;

    //         if (isset($coords[0]) && is_numeric($coords[0])) {
    //             $minX = min($minX, (float) $coords[0]);
    //             $maxX = max($maxX, (float) $coords[0]);
    //             $minY = min($minY, (float) ($coords[1] ?? 0));
    //             $maxY = max($maxY, (float) ($coords[1] ?? 0));
    //             return;
    //         }

    //         foreach ($coords as $c) $walk($c);
    //     };

    //     foreach ($features as $feature) {
    //         $coords = $feature['geometry']['coordinates'] ?? null;
    //         if ($coords !== null) $walk($coords);
    //     }

    //     if ($minX === PHP_FLOAT_MAX) return null;

    //     return [
    //         round($minX, 6),
    //         round($minY, 6),
    //         round($maxX, 6),
    //         round($maxY, 6),
    //     ];
    // }
}