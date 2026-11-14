<?php

use App\Http\Controllers\AdminStatsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MainPageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 (prefix /api/v1, see bootstrap/app.php)
|--------------------------------------------------------------------------
| Public: reading content (by slug) and comments, sending a contact message, auth endpoints.
| Authenticated: profile, orders, writing/deleting own comments, logout.
| Admin ("manage-content" gate): writing content, order management, contacts, statistics.
*/

$types = 'works|posts|products';

// Public content
Route::get('/main-page', MainPageController::class)->name('main-page');

Route::get('/works', [WorkController::class, 'index'])->name('works.index');
Route::get('/works/{work:slug}', [WorkController::class, 'show'])->name('works.show');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/{type}/{slug}/comments', [CommentController::class, 'index'])
    ->where('type', $types)
    ->name('comments.index');

Route::post('/contacts', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contacts.store');

// Guests only
Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register');

    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:password-reset')
        ->name('password.store');
});

// Signed-in users
Route::middleware('auth:sanctum')->group(function () use ($types) {
    Route::get('/me', fn (Request $request) => new UserResource($request->user()))->name('me');
    Route::patch('/me', [ProfileController::class, 'update'])->name('me.update');
    Route::put('/me/password', [ProfileController::class, 'changePassword'])->name('me.password');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/mine', [OrderController::class, 'mine'])->name('orders.mine');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');

    Route::post('/{type}/{slug}/comments', [CommentController::class, 'store'])
        ->where('type', $types)
        ->middleware('throttle:comments')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Administrators only
    Route::middleware('can:manage-content')->group(function () {
        Route::apiResource('works', WorkController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('posts', PostController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy']);

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        Route::get('/admin/stats', AdminStatsController::class)->name('admin.stats');
    });
});
