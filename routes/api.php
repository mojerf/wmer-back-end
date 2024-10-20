<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MainPageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;

Route::resource('contacts', ContactController::class)->only(['index', 'store', 'destroy']);
Route::resource('orders', OrderController::class)->only(['index', 'store', 'update']);
Route::resource('posts', PostController::class)->except(['create']);
Route::resource('products', ProductController::class)->except(['create']);
Route::resource('works', WorkController::class)->except(['create']);


Route::get('/main-page', [MainPageController::class, 'mainContent'])
    ->name('main page content');


Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
