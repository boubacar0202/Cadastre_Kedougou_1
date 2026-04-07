<?php
 
use App\Models\Dossier;
use App\Models\Terrain;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
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
use GuzzleHttp\Psr7\Request;
use Illuminate\Container\Attributes\Auth; 
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

Route::post('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout'); 

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    Route::get('/dossier/next', [DossierController::class, 'getNextDossier']);    
    Route::get('/departements/{region}', [LocationController::class, 'getDepartementsByRegion']);
    Route::get('/arrondissements/{departement}', [LocationController::class, 'getArrondissementByDepartement']);
    Route::get('/communes/{arrondissement}', [LocationController::class, 'getCommunesByArrondissement']); 
    Route::get('/lotissements/{id}', [LocationController::class, 'getLotissementsByCommune']);


    Route::resource('secretariat', SecretariatController::class);
    Route::resource('geometre', GeometreController::class);
    Route::resource('donnee', DonneeController::class);
    Route::resource('matriceCadastrale', MatriceCadastraleController::class);
    Route::resource('/message', MessageController::class);
    Route::resource('/mutation', MutationController::class); 
    Route::resource('lotissement', ParametreController::class); 
     
    Route::get('/donnee/create', [DonneeController::class, 'create'])->name('donnee.create'); 
    Route::get('/secretariat/dernier-ordre/{annee}', [SecretariatController::class, 'dernierOrdre']);
    
    Route::post('/dossier/verificationEnquete', [GeometreController::class, 'verify'])->name('dossier.verify');  
    Route::get('/secretariat', [SecretariatController::class, 'create'])->name('secretariat.create');
    Route::post('/secretariat', [SecretariatController::class, 'store'])->name('secretariat.store');
    Route::get('/geometre/create', [GeometreController::class, 'create'])->name('geometre.create');  

    Route::get('/dossier/last', function () {
        $lastDossier = Dossier::latest('id')->first(); 
        return response()->json([
            'num_dossier' => $lastDossier ? $lastDossier->txt_num_dossier : ''
        ]);
    });
    // code d'accée
    Route::post('/code/verify', [CodeAccesController::class, 'verify'])->name('code.verify'); 
    Route::get('/donnee/create', [DonneeController::class, 'create'])->name('donnee.create');

    // vérifier Nicad 
    Route::get('/verifier-nicad/{nicad}', [GeometreController::class, 'verifierNicad']); 
    Route::get('/secretariat/edit/{id}', [SecretariatController::class, 'edit'])->name('secretariat.edit');  
    Route::post('/secretariat/{terrain}/upload-pdf', [SecretariatController::class, 'uploadPDF'])->name('secretariat.uploadPDF');
    Route::put('/secretariat/{terrain}', [SecretariatController::class, 'update'])->name('secretariat.update'); 
    Route::post('/terrain/delete/{id}', [TerrainController::class, 'destroyTerrain'])->name('terrain.destroyTerrain');
    Route::post('/fetchReferencesLotissement', [SecretariatController::class, 'fetchReferencesLotissement']); 
    Route::post('/dossier/verifyDossier', [SecretariatController::class, 'verifyDossier'])->name('dossier.verifyDossier');

 
    // message 
    Route::middleware('auth')->group(function () {
        // Page d’écriture d’un message
        Route::get('/message/create', [MessageController::class, 'create'])->name('message.create'); 
        // Liste des messages entre utilisateurs
        Route::get('/message', [MessageController::class, 'index'])->name('message.index');
        Route::post('/message', [MessageController::class, 'store'])->name('message.store'); 
        // API
        Route::get('/api/messages/{withUserId}', [MessageController::class, 'apiIndex'])->name('message.apiIndex');
        Route::get('/message/{id}', [MessageController::class, 'show'])->name('message.show'); 
        // Non lus / reçus / lus
        Route::get('/messages/unread-total', [MessageController::class, 'unreadTotal']);
        Route::get('/messages/unread-by-user', [MessageController::class, 'unreadByUser']);
        Route::get('/messages/unread-per-user', [MessageController::class, 'unreadPerUser']);
        Route::post('/message/markRead/{senderId}', [MessageController::class, 'markRead'])->name('messages.markRead');
        Route::post('/message/markReceived/{senderId}', [MessageController::class, 'markReceived']);

        // CRUD
        Route::put('/message/{id}', [MessageController::class, 'update'])->name('message.update');
        Route::delete('/message/{id}', [MessageController::class, 'destroy'])->name('message.destroy');
    });

    // Routes sanctum si tu utilises API
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/messages/{receiverId}', [MessageController::class, 'messages']);
    });

    // mutation 
    Route::post('/dossier/verifyLotissement', [MutationController::class, 'verifyLotissement'])->name('dossier.verifyLotissement');
    Route::post('/fetchReferencesLotissement', [MutationController::class, 'fetchReferencesLotissement']); 
    Route::get('/mutation/create', [MutationController::class, 'create'])->name('mutation.create');
    Route::post('/mutation/store', [MutationController::class, 'store'])->name('mutation.store');


    // paramétrages
    Route::prefix('parametre')->group(function () {
        Route::get('/admin', [ParametreController::class, 'admin'])->name('parametre.admin'); 
        Route::delete('/admin/{id}', [ParametreController::class, 'destroyUser'])->name('parametre.admin.destroy'); 
        Route::get('/adminEdit/{id}', [ParametreController::class, 'editUser'])->name('parametre.admin.edit');  
        Route::put('/admin/update/{id}', [ParametreController::class, 'updateUser'])->name('parametre.updateUser');
        Route::get('/mutation', [ParametreController::class, 'mutation'])->name('parametre.mutation');
        Route::get('/mutationEdit/{id}', [ParametreController::class, 'editMutation'])->name('parametre.mutation.edit');
        Route::put('/mutation/update/{id}', [ParametreController::class, 'updateMutation'])->name('parametre.updateMutation'); 
        Route::delete('/mutation/destroy/{id}', [ParametreController::class, 'destroyMutation'])->name('parametre.mutation.destroy'); 

    
        //  formulaire
        Route::post('/store', [ParametreController::class, 'store'])->name('parametre.store');  
        // IMPORTANT : utilisation du contrôleur ici
        Route::get('/lotissement/index',    [ParametreController::class, 'index'])->name('parametre.lotissement.index');
        Route::get('/lotissement/create',   [ParametreController::class, 'create'])->name('parametre.lotissement.create');
        Route::get('/lotissement/edit/{id}', [ParametreController::class, 'edit'])->name('parametre.lotissement.edit'); 
        Route::put('/lotissement/update/{id}', [ParametreController::class, 'update'])->name('parametre.lotissement.update');
        Route::delete('/lotissement/destroy/{id}', [ParametreController::class, 'destroy'])->name('parametre.lotissement.destroy');
        Route::post('/code/verifyLotissement', [CodeAccesController::class, 'verifyLotissement'])->name('code.verifyLotissement');
    });
    
    // gerer la liste des lotissements 
    Route::get('/departements', [ParametreController::class, 'getDepartements']);
    Route::get('/arrondissements/{departement}', [ParametreController::class, 'getArrondissementByDepartement']);
    Route::get('/communes/{arrondissement}', [ParametreController::class, 'getCommunesByArrondissement']);   
});

require __DIR__ . '/auth.php';
