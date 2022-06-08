<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StreamerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('api')->group(function () {
    Route::get('/netflix_titles', [StreamerController::class, 'netflix'])->name('api.netflix.show');
    Route::get('/hulu_titles', [StreamerController::class, 'hulu'])->name('api.hulu.show');
    Route::get('/disney_plus_titles', [StreamerController::class, 'amazon'])->name('api.amazon.show');
    Route::get('/amazon_prime_titles', [StreamerController::class, 'disney'])->name('api.disney.show');
});
