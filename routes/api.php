<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsersController;

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

Route::put('/register', [UsersController::class, 'register']);
Route::put('/login', [UsersController::class, 'login']);
Route::post('/recuperar', [UsersController::class, 'passwordRecovery']);

// Dar de alta cartas y colecciones, sólo puede ser realizado por administradores
Route::middleware(['CheckToken','CheckAdministrator'])->group(function() {
    Route::put('/registercards', [UsersController::class, 'registerCards']);
    Route::put('/registercollections', [UsersController::class, 'registerCollections']);
    Route::put('/addcards', [UsersController::class, 'addCards']);
});

// Poner a la venta cartas, sólo puede ser realizado por profesionales y particulares
Route::middleware(['CheckToken','CheckSellers'])->group(function() {
    Route::put('/sell', [UsersController::class, 'sell']);
    Route::get('/searchselling', [UsersController::class, 'searchForSelling']); // Buscar cartas para vender
});

// Buscar cartas para comprar
Route::get('/searchbuying', [UsersController::class, 'searchForBuying']);
