<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImageFavoriteController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoFavoriteController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Http\Middleware\Check as JWTCheck;

Route::prefix('videos')->group(function () {
    Route::get('list', [VideoController::class, 'index']);

    Route::get('show/{video}', [VideoController::class, 'show']);

    Route::post('create', [VideoController::class, 'store']);

    Route::delete('{video}', [VideoController::class, 'destroy']);

    Route::post('{video}/favorite', [VideoFavoriteController::class, 'addFavorite']);

    Route::delete('{video}/favorite', [VideoFavoriteController::class, 'removeFavorite']);
});

Route::prefix('images')->group(function () {
    Route::get('list', [ImageController::class, 'index']);

    Route::get('show/{image}', [ImageController::class, 'show']);

    Route::post('create', [ImageController::class, 'store']);

    Route::delete('{image}', [ImageController::class, 'destroy']);

    Route::post('{image}/favorite', [ImageFavoriteController::class, 'addFavorite']);

    Route::delete('{image}/favorite', [ImageFavoriteController::class, 'removeFavorite']);
});

Route::prefix('user')->group(function () {
    Route::post('register', [AuthController::class, 'register']);

    Route::post('login', [AuthController::class, 'login']);

    Route::get('tokenProfile', [AuthController::class, 'getProfile']);

    Route::get('showUser/{id}', [AuthController::class, 'showUser']);

    Route::middleware([JwtMiddleware::class])->get('logout', [AuthController::class, 'logout']);
});

// Route::middleware('api')->group(function () {
//     Route::get('videos/list', [VideoController::class, 'index']);
//     Route::get('videos/show/{video}', [VideoController::class, 'show']);
// });

// 1. ROTAS PÚBLICAS OU HÍBRIDAS (Não barram visitantes, mas leem o token se ele existir)
Route::middleware([JWTCheck::class])->group(function () {
    Route::get('videos/list', [VideoController::class, 'index']);
    Route::get('videos/show/{video}', [VideoController::class, 'show']);
});

// 2. ROTAS RESTRITAS (Exigem login obrigatório e lançam exceção automaticamente)
Route::middleware(['auth:api'])->group(function () {
    Route::post('videos/create', [VideoController::class, 'store']);
    Route::delete('videos/{video}', [VideoController::class, 'destroy']);
    Route::post('videos/{video}/favorite', [VideoFavoriteController::class, 'addFavorite']);
});
