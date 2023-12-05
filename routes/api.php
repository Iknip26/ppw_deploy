<?php

use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\GreetController;
use App\Http\Controllers\InfoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::get('/info', [InfoController::class, 'index'])->name('info');

Route::get('greet', [GreetController::class,
'greet'])->name('greet');

Route::get('/gallery', [GalleryController::class,
'index'])->name('gallery');

Route::post('/gallery/store', [GalleryController::class,
'store'])->name('gallery.store');