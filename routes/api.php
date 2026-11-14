<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MainPageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WorkController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 (prefix /api/v1, see bootstrap/app.php)
|--------------------------------------------------------------------------
| Public: reading content, sending a contact message, logging in/registering.
| Authenticated: placing orders, current user, logout.
| Admin ("manage-content" gate): writing content, reading orders and contacts.
*/

// Public
Route::get('/main-page', MainPageController::class)->name('main-page');

Route::apiResource('works', WorkController::class)->only(['index', 'show']);
Route::apiResource('posts', PostController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);

Route::post('/contacts', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contacts.store');

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register');
});

// Authenticated users
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (Request $request) => new UserResource($request->user()))->name('me');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

    // Administrators only
    Route::middleware('can:manage-content')->group(function () {
        Route::apiResource('works', WorkController::class)->except(['index', 'show']);
        Route::apiResource('posts', PostController::class)->except(['index', 'show']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    });
});
