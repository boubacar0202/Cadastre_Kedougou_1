<?php
 
use App\Models\Dossier;
use App\Models\Terrain;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\ArriveeController;
use App\Http\Controllers\DepartController;
use App\Http\Controllers\InstanceController;
use App\Http\Controllers\InstanceArriveeController;
use App\Http\Controllers\InstanceDepartController;
use App\Http\Controllers\BaremeEvaluationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TrimestreController;
use App\Http\Controllers\CodeAccesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonneeController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\GeometreController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MatriceCadastraleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecretariatController;
use App\Http\Controllers\TerrainController; 
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\PlanController;  
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\ShapefileController;
use App\Models\PointDensifier; 
use Illuminate\Foundation\Application; 
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout'); 

Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    ->middleware('guest')
    ->name('password.request');

Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email'); 

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // ✅ Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
 
    // ✅ Locations
    Route::get('/dossier/next', [DossierController::class, 'getNextDossier']);    
    Route::get('/departements/{region}', [LocationController::class, 'getDepartementsByRegion']);
    Route::get('/arrondissements/{departement}', [LocationController::class, 'getArrondissementByDepartement']);
    Route::get('/communes/{arrondissement}', [LocationController::class, 'getCommunesByArrondissement']); 
    Route::get('/lotissements/{id}', [LocationController::class, 'getLotissementsByCommune']);
 
    // ✅ Resources
    Route::resource('secretariat', SecretariatController::class);
    Route::resource('geometre', GeometreController::class);
    Route::resource('donnee', DonneeController::class);
    Route::resource('matriceCadastrale', MatriceCadastraleController::class);
    Route::resource('/message', MessageController::class);
    Route::resource('mutation', MutationController::class); 
    Route::get('/lotissement', [ParametreController::class, 'index'])->name('lotissement.index');
    Route::resource('lotissement', ParametreController::class)->except(['index'])->middleware('role:admin'); 
    Route::resource('plan', PlanController::class);

    // ✅ Arrivee
    Route::get('/arrivee/next/{annee}', [ArriveeController::class, 'getNextOrdre'])->name('arrivee.next');
    Route::delete('/arrivee/{id}/pdf', [ArriveeController::class, 'deletePdf'])->name('arrivee.pdf.destroy');
    Route::get('/arrivee/editarrivee/{id}', [ArriveeController::class, 'edit'])->name('arrivee.edit');
    Route::resource('arrivee', ArriveeController::class)->only(['create', 'store', 'update']); 

    // ✅ Depart
    Route::post('/fetch-references-arrivee', [DepartController::class, 'fetchReferencesArrivee'])->name('depart.references');
    Route::get('/depart/next/{annee}', [DepartController::class, 'getNextOrdre'])->name('depart.next');
    Route::delete('/depart/{id}/pdf', [DepartController::class, 'deletePdf'])->name('depart.pdf.destroy');
    Route::get('/depart/editdepart/{id}', [DepartController::class, 'edit'])->name('depart.edit');
    Route::resource('depart', DepartController::class)->only(['create', 'store', 'update']); 
    Route::delete('/instancearrivee/arrivee/{id}', [InstancearriveeController::class, 'destroy'])->name('instancearrivee.destroyArrivee');
    Route::get('/instancearrivee/arrivee/{txt_numdordre}', [InstancearriveeController::class, 'show'])->name('instancearrivee.show');
    Route::delete('/instancedepart/depart/{id}', [InstancedepartController::class, 'destroyDepart'])->name('instancedepart.destroyDepart'); 

    // ✅ Instance
    Route::resource('instance', InstanceController::class);
    Route::resource('instancearrivee', InstanceArriveeController::class);
    Route::resource('instancedepart', InstanceDepartController::class);

    // ✅ Données
    Route::get('/donnees', [DonneeController::class, 'create'])->name('donnees.create');  
    Route::delete('/terrains/{id}', [TerrainController::class, 'destroyTerrain'])->name('terrain.destroy');
    Route::post('/verifier-code-admin', [TerrainController::class, 'verifierCodeAdmin'])->name('verifier.code.admin');
    
    // ✅ Secretariat - Routes spécifiques
    Route::get('/secretariat/edit/{id}', [SecretariatController::class, 'edit'])->name('secretariat.edit');
    Route::get('/secretariat/dernier-ordre/{annee}', [SecretariatController::class, 'dernierOrdre']); 
    Route::get('/secretariat', [SecretariatController::class, 'create'])->name('secretariat.create');
    Route::post('/secretariat', [SecretariatController::class, 'store'])->name('secretariat.store');
    Route::put('/secretariat/{id}', [SecretariatController::class, 'update'])->name('secretariat.update'); 
    
    // ✅ Geometre
    Route::get('/geometre/create', [GeometreController::class, 'create'])->name('geometre.create');  
    Route::post('/dossier/verificationEnquete', [GeometreController::class, 'verify'])->name('dossier.verify');  

    // ✅ Dossier
    Route::get('/dossier/last', function () {
        $lastDossier = Dossier::latest('id')->first(); 
        return response()->json([
            'num_dossier' => $lastDossier ? $lastDossier->txt_num_dossier : ''
        ]);
    });
    
    // ✅ Code d'accès
    Route::post('/code/verify', [CodeAccesController::class, 'verify'])->name('code.verify'); 

    // ✅ Vérifier Nicad
    Route::get('/verifier-nicad/{nicad}', [GeometreController::class, 'verifierNicad']);  
    Route::post('/secretariat/{terrain}/upload-pdf', [SecretariatController::class, 'uploadPDF'])->name('secretariat.uploadPDF');
    
    // ✅ Terrain
    Route::post('/terrain/delete/{id}', [TerrainController::class, 'destroyTerrain'])->name('terrain.destroyTerrain');
    Route::post('/fetchReferencesLotissement', [SecretariatController::class, 'fetchReferencesLotissement']); 
    Route::post('/dossier/verifyDossier', [SecretariatController::class, 'verifyDossier'])->name('dossier.verifyDossier');
    
    // ✅ Matrice
    Route::get('/matrice', [MatriceCadastraleController::class, 'create'])->name('matrice.create'); 
    Route::delete('/matrice/{id}', [MatriceCadastraleController::class, 'destroy'])->name('matrice.destroy');
 
    // ✅ Messages
    Route::middleware('auth')->group(function () {
        Route::get('/message/create', [MessageController::class, 'create'])->name('message.create'); 
        Route::get('/message', [MessageController::class, 'index'])->name('message.index');
        Route::post('/message', [MessageController::class, 'store'])->name('message.store'); 
        Route::get('/api/messages/{withUserId}', [MessageController::class, 'apiIndex'])->name('message.apiIndex');
        Route::get('/message/{id}', [MessageController::class, 'show'])->name('message.show'); 
        Route::get('/messages/unread-total', [MessageController::class, 'unreadTotal']);
        Route::get('/messages/unread-by-user', [MessageController::class, 'unreadByUser']);
        Route::get('/messages/unread-per-user', [MessageController::class, 'unreadPerUser']);
        Route::post('/message/markRead/{senderId}', [MessageController::class, 'markRead'])->name('messages.markRead');
        Route::post('/message/markReceived/{senderId}', [MessageController::class, 'markReceived']);
        Route::put('/message/{id}', [MessageController::class, 'update'])->name('message.update');
        Route::delete('/message/{id}', [MessageController::class, 'destroy'])->name('message.destroy');
    });

    // ✅ Mutation
    Route::post('/fetchReferencesLotissement', [MutationController::class, 'fetchReferencesLotissement']); 
    Route::post('/dossier/verifyLotissement', [MutationController::class, 'verifyLotissement']);
    Route::get('/mutation/create', [MutationController::class, 'create'])->name('mutation.create');
    Route::post('/mutation/store', [MutationController::class, 'store'])->name('mutation.store');
    Route::delete('/pointDensifier/destroy/{id}', [ParametreController::class, 'destroyPoint'])
        ->middleware('role:admin')
        ->name('parametre.pointDensifier.destroy');

        
    // 🏘️ Lotissements : la liste est ouverte, l'ajout et la modification restent aux administrateurs
    Route::get('/lotissement/index', [ParametreController::class, 'index'])->name('parametre.lotissement.index');
    Route::post('/dossier/verifyLotissement', [MutationController::class, 'verifyLotissement'])->name('dossier.verifyLotissement');
    Route::middleware('role:admin')->group(function () {
        Route::post('/store', [ParametreController::class, 'store'])->name('parametre.store');
        Route::get('/lotissement/create', [ParametreController::class, 'create'])->name('parametre.lotissement.create');
        Route::get('/lotissement/edit/{id}', [ParametreController::class, 'edit'])->name('parametre.lotissement.edit');
        Route::put('/lotissement/update/{id}', [ParametreController::class, 'update'])->name('parametre.lotissement.update');
        Route::delete('/lotissement/destroy/{id}', [ParametreController::class, 'destroy'])->name('parametre.lotissement.destroy');
    });
 
    // ✅ Paramétrages — réservé aux administrateurs
    Route::prefix('parametre')->middleware('role:admin')->group(function () {
        // 👤 Admin - Gestion des utilisateurs
        Route::get('/admin', [ParametreController::class, 'admin'])->name('parametre.admin');
        Route::get('/user', [ParametreController::class, 'user'])->name('parametre.user');
        Route::post('/admin/store', [ParametreController::class, 'storeUser'])->name('parametre.admin.store');
        Route::post('/admin/{id}/validate', [ParametreController::class, 'validateUser'])->name('parametre.admin.validate');
        Route::delete('/admin/{id}', [ParametreController::class, 'destroyUser'])->name('parametre.admin.destroy'); 
        Route::get('/adminEdit/{id}', [ParametreController::class, 'editUser'])->name('parametre.admin.edit');  
        Route::put('/admin/update/{id}', [ParametreController::class, 'updateUser'])->name('parametre.updateUser');
        
        // 📋 Mutations
        Route::get('/mutation', [ParametreController::class, 'mutation'])->name('parametre.mutation');
        Route::get('/mutationEdit/{id}', [ParametreController::class, 'editMutation'])->name('parametre.mutation.edit');
        Route::put('/mutation/update/{id}', [ParametreController::class, 'updateMutation'])->name('parametre.updateMutation'); 
        Route::delete('/mutation/destroy/{id}', [ParametreController::class, 'destroyMutation'])->name('parametre.mutation.destroy'); 
 
        // 🏘️ Points
        Route::get('/pointDensifier/createPoint', [ParametreController::class, 'createPoint'])->name('parametre.pointDensifier.createPoint'); 
        Route::post('/pointDensifier/storePoint', [ParametreController::class, 'storePoint'])->name('parametre.pointDensifier.storePoint');
        Route::get('/pointDensifier/edit/{id}', [ParametreController::class, 'editPoint'])->name('parametre.pointDensifier.editPoint');
        Route::put('/pointDensifier/updatePoint/{id}', [ParametreController::class, 'updatePoint'])->name('parametre.pointDensifier.updatePoint');        Route::get('/api/points-densifies', function () {
            return PointDensifier::all([
                'id', 'txt_nomPoint', 'txt_descriptionPoint', 
                'txt_situationPoint', 'txt_estPoint', 'txt_nordPoint', 
                'txt_hauteurPoint', 'fichierPDF'
            ]);
        });
  
        // 🔑 Vérification de code
        Route::post('/code/verifyLotissement', [CodeAccesController::class, 'verifyLotissement'])->name('code.verifyLotissement'); 
        Route::post('/code/verifyPoint', [CodeAccesController::class, 'verifyPoint'])->name('code.verifyPoint'); 
        
        // 📊 Chart / Évaluation - Page des graphiques
        Route::get('/chart', [ParametreController::class, 'chart'])->name('parametre.chart');

        // Recueil de textes
        Route::get('/service/create', [ServiceController::class, 'create'])->name('parametre.service.create');
        Route::post('/service', [ServiceController::class, 'store'])->name('parametre.service.store');
        Route::get('/service/edit/{service}', [ServiceController::class, 'edit'])->name('parametre.service.edit');
        Route::put('/service/{service}', [ServiceController::class, 'update'])->name('parametre.service.update');
        Route::delete('/service/{service}', [ServiceController::class, 'destroy'])->name('parametre.service.destroy');

        // Trimestres
        Route::get('/trimestre/create', [TrimestreController::class, 'create'])->name('parametre.trimestre.create');

        // Barèmes d'évaluation
        Route::get('/bareme', [BaremeEvaluationController::class, 'index'])->name('parametre.bareme.index');
        Route::post('/bareme', [BaremeEvaluationController::class, 'store'])->name('parametre.bareme.store');
        Route::put('/bareme/{bareme}', [BaremeEvaluationController::class, 'update'])->name('parametre.bareme.update');
        Route::delete('/bareme/{bareme}', [BaremeEvaluationController::class, 'destroy'])->name('parametre.bareme.destroy');
    });

    // ✅ Routes API pour les graphiques d'évaluation — réservé aux administrateurs
    Route::middleware('role:admin')->group(function () {
        Route::get('/api/chart-data', [ParametreController::class, 'getChartData'])->name('api.chart.data');
        Route::get('/api/stats', [ParametreController::class, 'getStatsData'])->name('api.stats');
        Route::get('/api/cession-data', [ParametreController::class, 'getCessionData'])->name('api.cession.data');
    });

    // ✅ Listes des lotissements
    Route::get('/departements', [ParametreController::class, 'getDepartements']);
    Route::get('/arrondissements/{departement}', [ParametreController::class, 'getArrondissementByDepartement']);
    Route::get('/communes/{arrondissement}', [ParametreController::class, 'getCommunesByArrondissement']);  
    Route::post('/terrain/search', [TerrainController::class, 'searchTerrain']); 
    Route::get('/parcelles/{numero}', [TerrainController::class, 'show']);

    // ✅ Shapefiles  

    // Page du plan
    Route::get('/plan/create', [ShapefileController::class, 'index'])->name('plan.create');

    // Liste + CRUD Shapefiles
    Route::get('/shapefiles', [ShapefileController::class, 'list'])->name('shapefiles.list');
    Route::post('/shapefiles', [ShapefileController::class, 'store'])->name('shapefiles.store');
    Route::patch('/shapefiles/{shapefile}', [ShapefileController::class, 'update'])->name('shapefiles.update');
    Route::delete('/shapefiles/{shapefile}', [ShapefileController::class, 'destroy'])->name('shapefiles.destroy');

    // API GeoJSON d'un shapefile
    Route::get('/api/shapefiles/{shapefile}/geojson', [ShapefileController::class, 'geojson'])->name('shapefiles.geojson');

    // Génération de polygones à partir d'un centroïde
    Route::post('/api/generer-polygone', [ShapefileController::class, 'genererPolygone'])->name('api.generer.polygone');
    Route::post('/generer-angles', [ShapefileController::class, 'genererAngles'])->name('generer.angles'); 

});

require __DIR__ . '/auth.php';