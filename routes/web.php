<?php

use App\Models\Dossier;
use App\Models\Terrain;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CodeAccesController;
use App\Http\Controllers\DonneeController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\GeometreController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MatriceCadastraleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecretariatController;
use App\Http\Controllers\TerrainController; 
use App\Http\Controllers\MessageController;
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



Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dossier/next', [DossierController::class, 'getNextDossier']);  
Route::get('/departements/{regionId}', [LocationController::class, 'getDepartementsByRegion']);
Route::get('/arrondissements/{departementId}', [LocationController::class, 'getArrondissementByDepartement']);
Route::get('/communes/{arrondissementId}', [LocationController::class, 'getCommunesByArrondissement']);
Route::resource('secretariat', SecretariatController::class);
Route::resource('geometre', GeometreController::class);
Route::resource('donnee', DonneeController::class);
Route::resource('matriceCadastrale', MatriceCadastraleController::class);
Route::resource('/message', MessageController::class);
Route::get('/secretariat/create', [SecretariatController::class, 'create'])->name('secretariat.create');
Route::get('/donnee/create', [DonneeController::class, 'create'])->name('donnee.create'); 
Route::get('/secretariat/dernier-ordre/{annee}', [SecretariatController::class, 'dernierOrdre']);

  
Route::post('dossier/verify', [GeometreController::class, 'verify'])->name('dossier.verify');
 
Route::get('/secretariat', [SecretariatController::class, 'create'])->name('secretariat.create');
Route::post('/secretariat', [SecretariatController::class, 'store'])->name('secretariat.store');
Route::get('/geometre/create', [GeometreController::class, 'create'])->name('geometre.create'); 
// Route::get('/geometre/create/{txt_num_dossier}', [GeometreController::class, 'create'])->name('geometre.create');

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
 
// Route::put('/secretariat/update/{id}', [SecretariatController::class, 'update'])->name('secretariat.update'); 
Route::get('/secretariat/edit/{id}', [SecretariatController::class, 'edit'])->name('secretariat.edit'); 
 
Route::post('/secretariat/{terrain}/upload-pdf', [SecretariatController::class, 'uploadPDF'])->name('secretariat.uploadPDF');
Route::put('/secretariat/{terrain}', [SecretariatController::class, 'update'])->name('secretariat.update');
 
Route::post('/terrain/verifier-supprimer', [TerrainController::class, 'verifierEtSupprimer'])->name('terrain.verifier.supprimer');

// message
// Groupe protégé par middleware auth
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


// Route::get('/message/create', [MessageController::class, 'create'])->name('message.create');

// Route::get('/message', [MessageController::class, 'index']);
// Route::post('/message', [MessageController::class, 'store']);

// Route::get('/message', [MessageController::class, 'index'])->middleware('auth');
// Route::post('/message', [MessageController::class, 'store'])->middleware('auth');
 
// Route::get('/api/messages/{withUserId}', [MessageController::class, 'apiIndex'])->middleware('auth');
// Route::get('/message/{id}', [MessageController::class, 'show'])->name('message.show');

// Route::middleware('auth')->group(function () {
//     Route::get('/messages/unread-total', [MessageController::class, 'unreadTotal']);
//     Route::get('/messages/unread-by-user', [MessageController::class, 'unreadByUser']);
// });
 
// Route::post('/message/markRead/{senderId}', [MessageController::class, 'markRead'])->name('messages.markRead');
 
// Route::get('/api/messages/{id}', [MessageController::class, 'fetchMessages']); 
// Route::post('/message', [MessageController::class, 'store']);
// Route::post('/message/markReceived/{senderId}', [MessageController::class, 'markReceived']);
// Route::middleware('auth:sanctum')->get('/messages/{receiverId}', [MessageController::class, 'messages']);
// Route::get('/messages/unread-per-user', [MessageController::class, 'unreadPerUser'])->middleware('auth:sanctum');
// Route::delete('/message/{id}', [MessageController::class, 'destroy'])->middleware('auth');
// Route::put('/message/{id}', [MessageController::class, 'update'])->middleware('auth');
  
 



require __DIR__ . '/auth.php';
