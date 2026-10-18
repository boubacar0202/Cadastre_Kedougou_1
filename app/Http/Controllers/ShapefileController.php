<?php

namespace App\Http\Controllers;

use App\Models\PointDensifier;
use App\Models\Shapefile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Shapefile\ShapefileReader;

class ShapefileController extends Controller
{
    private const TYPES_ENTITE = [
        'limite_region',
        'limite_departement',
        'limite_arrondissement',
        'limite_commune',
        'limite_section',
        'limite_parcelle',
        'numero_section',
        'numero_parcelle',
    ];

    private const COULEURS_ENTITE = [
        'limite_region'         => '#1e3a8a',
        'limite_departement'    => '#0f766e',
        'limite_arrondissement' => '#6d28d9',
        'limite_commune'        => '#c2410c',
        'limite_section'        => '#9a3412',
        'limite_parcelle'       => '#052e16',
        'numero_section'        => '#7c2d12',
        'numero_parcelle'       => '#052e16',
    ];
    /**
     * ============================================================
     * PAGE PRINCIPALE
     * ============================================================
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
     * ============================================================
     * LISTE JSON (pour axios après upload)
     * ============================================================
     */
    public function list()
    {
        return response()->json(Shapefile::latest()->get());
    }

    /**
     * ============================================================
     * IMPORT D'UN SHAPEFILE
     * ============================================================
     * Étapes :
     *  1. Validation
     *  2. Vérification extensions
     *  3. Dossier temporaire
     *  4. Lecture avec php-shapefile
     *  5. Nettoyage (PolygonM → Polygon, Z/M)
     *  6. Reprojection UTM 28N → WGS84
     *  7. Stockage GeoJSON
     *  8. Enregistrement en base
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'nom'         => 'required|string|max:255',
            'type_entite' => 'required|string|in:' . implode(',', self::TYPES_ENTITE),
            'shp'         => 'required|file',
            'shx'         => 'required|file',
            'dbf'         => 'required|file',
            'prj'         => 'nullable|file',
            'srid'        => 'nullable|string|max:10',
        ], [
            'nom.required' => 'Le nom du plan est requis.',
            'type_entite.required' => 'Le type d\'entité est requis.',
            'type_entite.in' => 'Le type d\'entité choisi est invalide.',
            'shp.required' => 'Le fichier .shp est requis.',
            'shx.required' => 'Le fichier .shx est requis.',
            'dbf.required' => 'Le fichier .dbf est requis.',
        ]);

        try {
            $geojson = $this->construireGeojsonDepuisUpload($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->withErrors(['shp' => 'Erreur lecture : ' . $e->getMessage()]);
        }

        $features = $geojson['features'];

        $nbEntites = count($features);
        $bbox      = $this->computeBbox($features);

        // 8. Stocker définitivement
        $nomTable  = 'shp_' . Str::lower(Str::random(10));
        $finalPath = "shapefiles/{$nomTable}.geojson";
        Storage::disk('public')->put($finalPath, json_encode($geojson));

        // 10. Enregistrer en base
        $typeEntite = $request->input('type_entite');

        $shapefile = Shapefile::create([
            'nom'         => $request->nom,
            'type_entite' => $typeEntite,
            'nom_table'   => $nomTable,
            'srid'        => '4326',
            'nb_entites'  => $nbEntites,
            'bbox'        => $bbox,
            'couleur'     => self::COULEURS_ENTITE[$typeEntite] ?? '#3388ff',
            'visible'      => true,
            'verification' => $this->verificationUploadee($request),
            // 'user_id'    => auth()->id(),
        ]);

        return back()->with('success', "✅ Shapefile #{$shapefile->id} importé ({$nbEntites} entités)");
    }

    private function verificationUploadee(Request $request): ?array
    {
        if (!$request->filled('verification')) {
            return null;
        }

        $verification = json_decode($request->input('verification'), true);
        if (!is_array($verification) || !in_array($verification['type'] ?? '', ['parcelle', 'projet'], true)) {
            return null;
        }

        return $verification;
    }

    public function previsualiser(Request $request)
    {
        $request->validate([
            'shp'  => 'required|file',
            'shx'  => 'required|file',
            'dbf'  => 'required|file',
            'prj'  => 'nullable|file',
            'srid' => 'nullable|string|max:10',
        ]);

        try {
            return response()->json($this->construireGeojsonDepuisUpload($request));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lecture : ' . $e->getMessage()], 422);
        }
    }

    private function construireGeojsonDepuisUpload(Request $request): array
    {
        $expected = ['shp' => 'shp', 'shx' => 'shx', 'dbf' => 'dbf', 'prj' => 'prj'];
        $errors = [];
        foreach ($expected as $field => $ext) {
            if ($request->hasFile($field)) {
                $actual = strtolower($request->file($field)->getClientOriginalExtension());
                if ($actual !== $ext) {
                    $errors[$field] = "Le fichier doit avoir l'extension .{$ext} (reçu : .{$actual})";
                }
            }
        }
        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        $folder = 'tmp_shp_' . Str::uuid();
        $tmpPath = storage_path("app/{$folder}");
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0775, true);
        }

        try {
            foreach (['shp', 'shx', 'dbf', 'prj'] as $ext) {
                if ($request->hasFile($ext)) {
                    $request->file($ext)->move($tmpPath, "shape.{$ext}");
                }
            }

            $reader = new ShapefileReader("{$tmpPath}/shape.shp");
            $features = [];
            while ($record = $reader->fetchRecord()) {
                if ($record->isDeleted()) {
                    continue;
                }

                $geom = json_decode($record->getGeoJSON(), true);
                if (!$geom || !isset($geom['type'])) {
                    continue;
                }

                $geom['type'] = preg_replace('/(M|Z|ZM)$/i', '', $geom['type']);
                unset($geom['bbox']);
                $geom['coordinates'] = $this->cleanCoordinates($geom['coordinates']);
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => $geom,
                    'properties' => $record->getDataArray(),
                ];
            }

            $geojson = [
                'type' => 'FeatureCollection',
                'features' => $features,
            ];

            $srid = $request->input('srid', '4326');
            $firstCoord = $this->getFirstCoordinate($features);
            $needsReprojection = in_array($srid, ['32628', '2154'])
                || ($firstCoord && (abs($firstCoord[0]) > 180 || abs($firstCoord[1]) > 90));
            if ($needsReprojection) {
                $this->reprojectAllFeatures($geojson['features']);
            }

            return $geojson;
        } finally {
            array_map('unlink', glob("{$tmpPath}/*") ?: []);
            @rmdir($tmpPath);
        }
    }

    /**
     * ============================================================
     * RENVOIE LE GEOJSON D'UN SHAPEFILE
     * ============================================================
     */
    public function geojson(Shapefile $shapefile)
    {
        $path = storage_path("app/public/shapefiles/{$shapefile->nom_table}.geojson");

        if (!file_exists($path)) {
            return response()->json([
                'error'     => 'Fichier introuvable',
                'path'      => $path,
                'nom_table' => $shapefile->nom_table,
            ], 404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * ============================================================
     * SUPPRESSION
     * ============================================================
     */
    public function destroy(Request $request, Shapefile $shapefile)
    {
        $path = "shapefiles/{$shapefile->nom_table}.geojson";
        Storage::disk('public')->delete($path);
        $shapefile->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shapefile supprimé',
            ]);
        }

        return back()->with('success', '✅ Shapefile supprimé');
    }

    /**
     * ============================================================
     * MISE À JOUR (nom, couleur, visible)
     * ============================================================
     */
    public function update(Request $request, Shapefile $shapefile)
    {
        $validated = $request->validate([
            'nom'         => 'sometimes|string|max:255',
            'type_entite' => 'sometimes|nullable|string|in:' . implode(',', self::TYPES_ENTITE),
            'couleur'     => 'sometimes|string|max:20',
            'visible'     => 'sometimes|boolean',
        ]);
        $shapefile->update($validated);
        return response()->json($shapefile);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Nettoie les coordonnées :
     * - Supprime Z et M (garde seulement [x, y])
     * - Arrondit à 8 décimales
     */
    private function cleanCoordinates($coords)
    {
        if (!is_array($coords)) return $coords;

        if (isset($coords[0]) && is_numeric($coords[0])) {
            $x = (float) $coords[0];
            $y = (float) $coords[1];

            // ✅ Détection UTM : x > 180 (mètres) ou y > 90
            if (abs($x) > 180 || abs($y) > 90) {
                [$x, $y] = $this->utm28nToWgs84($x, $y);
            }

            return [round($x, 8), round($y, 8)];
        }

        return array_map(fn($c) => $this->cleanCoordinates($c), $coords);
    }

    /**
     * Récupère les premières coordonnées d'un ensemble de features.
     * Sert à détecter si c'est de l'UTM (x > 180) ou du WGS84.
     */
    private function getFirstCoordinate(array $features): ?array
    {
        foreach ($features as $feature) {
            $coords = $feature['geometry']['coordinates'] ?? null;
            if ($coords) {
                $first = $this->extractFirstPoint($coords);
                if ($first) return $first;
            }
        }
        return null;
    }

    /**
     * Extrait le premier point [x, y] d'une structure de coordonnées imbriquées
     */
    private function extractFirstPoint($coords): ?array
    {
        if (!is_array($coords)) return null;
        if (isset($coords[0]) && is_numeric($coords[0])) {
            return [(float) $coords[0], (float) $coords[1]];
        }
        foreach ($coords as $c) {
            $pt = $this->extractFirstPoint($c);
            if ($pt) return $pt;
        }
        return null;
    }

    /**
     * Reprojette tous les features UTM 28N → WGS84
     */
    private function reprojectAllFeatures(array &$features)
    {
        $proj4 = new \proj4php\Proj4php();
        $from = new \proj4php\Proj('+proj=utm +zone=28 +datum=WGS84 +units=m +no_defs', $proj4);
        $to   = new \proj4php\Proj('+proj=longlat +datum=WGS84 +no_defs', $proj4);

        foreach ($features as &$feature) {
            $feature['geometry']['coordinates'] = $this->reprojectCoords(
                $feature['geometry']['coordinates'], $from, $to, $proj4
            );
        }
        unset($feature);
    }

    /**
     * Reprojection récursive d'un tableau de coordonnées
     */
    private function reprojectCoords($coords, $from, $to, $proj4)
    {
        if (!is_array($coords)) return $coords;

        if (isset($coords[0]) && is_numeric($coords[0])) {
            try {
                $point  = new \proj4php\Point($coords[0], $coords[1], $from);
                $result = $proj4->transform($to, $point);

                // ✅ proj4php retourne un objet avec ->x et ->y
                return [
                    round($result->x, 8),
                    round($result->y, 8),
                ];
            } catch (\Exception $e) { 
                return $coords;
            }
        }

        return array_map(
            fn($c) => $this->reprojectCoords($c, $from, $to, $proj4),
            $coords
        );
    }

    /**
     * Calcule la bounding box d'un ensemble de features GeoJSON
     */
    private function computeBbox(array $features): ?array
    {
        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = -PHP_FLOAT_MAX;

        $walk = function ($coords) use (&$walk, &$minX, &$minY, &$maxX, &$maxY) {
            if (!is_array($coords)) return;
            if (isset($coords[0]) && is_numeric($coords[0])) {
                $minX = min($minX, (float) $coords[0]);
                $maxX = max($maxX, (float) $coords[0]);
                $minY = min($minY, (float) ($coords[1] ?? 0));
                $maxY = max($maxY, (float) ($coords[1] ?? 0));
                return;
            }
            foreach ($coords as $c) $walk($c);
        };

        foreach ($features as $f) {
            if (isset($f['geometry']['coordinates'])) {
                $walk($f['geometry']['coordinates']);
            }
        }

        return $minX === PHP_FLOAT_MAX
            ? null
            : [round($minX, 6), round($minY, 6), round($maxX, 6), round($maxY, 6)];
    }

    /**
     * ✅ LA FONCTION ICI (en bas de la classe)
     */
    private function genererAnglesPolygone(
        float $xCentroid,
        float $yCentroid,
        int   $n,
        float $rayon,
        float $orientation = 90
    ): array {
        if ($n < 3) {
            throw new \InvalidArgumentException("Un polygone a au moins 3 angles");
        }

        $points = [];
        $ecartAngulaire = 360 / $n;

        for ($i = 0; $i < $n; $i++) {
            $angleDeg = $orientation + $i * $ecartAngulaire;
            $angleRad = deg2rad($angleDeg);

            $points[] = [
                'x' => round($xCentroid + $rayon * cos($angleRad), 2),
                'y' => round($yCentroid + $rayon * sin($angleRad), 2),
            ];
        }

        return $points;
    }

        /**
     * Convertit UTM 28N (EPSG:32628) → WGS84 (lat/lng)
     * Testé et validé pour le Sénégal
     *
     * @param float $easting   X UTM (ex: 800948.00)
     * @param float $northing  Y UTM (ex: 1389186.00)
     * @return array           [longitude, latitude] en degrés décimaux
     */
    private function utm28nToWgs84(float $easting, float $northing): array
    {
        // Paramètres WGS84
        $a  = 6378137.0;
        $f  = 1 / 298.257223563;
        $e2 = 2 * $f - $f * $f;
        $e1 = (1 - sqrt(1 - $e2)) / (1 + sqrt(1 - $e2));

        // Paramètres UTM zone 28N
        $k0  = 0.9996;
        $lon0 = deg2rad(-15.0);  // méridien central zone 28

        // Coordonnées relatives au méridien central
        $x = $easting - 500000.0;
        $y = $northing;

        // Latitude pied de la perpendiculaire
        $M  = $y / $k0;
        $mu = $M / ($a * (1 - $e2 / 4 - 3 * pow($e2, 2) / 64 - 5 * pow($e2, 3) / 256));

        $phi1 = $mu
            + (3 * $e1 / 2 - 27 * pow($e1, 3) / 32) * sin(2 * $mu)
            + (21 * $e1 * $e1 / 16 - 55 * pow($e1, 4) / 32) * sin(4 * $mu)
            + (151 * pow($e1, 3) / 96) * sin(6 * $mu);

        $sinPhi1 = sin($phi1);
        $cosPhi1 = cos($phi1);
        $tanPhi1 = tan($phi1);

        $N1 = $a / sqrt(1 - $e2 * $sinPhi1 * $sinPhi1);
        $T1 = $tanPhi1 * $tanPhi1;
        $C1 = ($e2 / (1 - $e2)) * $cosPhi1 * $cosPhi1;
        $R1 = $a * (1 - $e2) / pow(1 - $e2 * $sinPhi1 * $sinPhi1, 1.5);
        $D  = $x / ($N1 * $k0);

        // Latitude
        $lat = $phi1 - ($N1 * $tanPhi1 / $R1) * (
            pow($D, 2) / 2
            - (5 + 3 * $T1 + 10 * $C1 - 4 * $C1 * $C1 - 9 * ($e2 / (1 - $e2))) * pow($D, 4) / 24
            + (61 + 90 * $T1 + 298 * $C1 + 45 * $T1 * $T1
                - 252 * ($e2 / (1 - $e2))
                - 3 * $C1 * $C1) * pow($D, 6) / 720
        );

        // Longitude
        $lon = $lon0 + (
            $D
            - (1 + 2 * $T1 + $C1) * pow($D, 3) / 6
            + (5 - 2 * $C1 + 28 * $T1 - 3 * $C1 * $C1
                + 8 * ($e2 / (1 - $e2))
                + 24 * $T1 * $T1) * pow($D, 5) / 120
        ) / $cosPhi1;

        return [rad2deg($lon), rad2deg($lat)];
    }

    /**
     * ✅ MÉTHODE PUBLIQUE POUR L'UTILISER (appelée par une route)
     */
    public function genererAngles(Request $request)
    {
        $request->validate([
            'x_centroid'  => 'required|numeric',
            'y_centroid'  => 'required|numeric',
            'n'           => 'required|integer|min:3',
            'rayon'       => 'required|numeric|min:0.1',
            'orientation' => 'nullable|numeric',
        ]);

        $angles = $this->genererAnglesPolygone(
            (float) $request->x_centroid,
            (float) $request->y_centroid,
            (int) $request->n,
            (float) $request->rayon,
            (float) ($request->orientation ?? 90)
        );

        // Convertir en WGS84
        $anglesWGS84 = array_map(function ($pt) {
            [$lng, $lat] = $this->utm28nToWgs84($pt['x'], $pt['y']);
            return ['lat' => $lat, 'lng' => $lng];
        }, $angles);

        return response()->json([
            'centroid'     => ['x' => $request->x_centroid, 'y' => $request->y_centroid],
            'angles_utm'   => $angles,
            'angles_wgs84' => $anglesWGS84,
        ]);
    }
}