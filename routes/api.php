<?php

use App\Http\Controllers\PuzzlesController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

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

Route::get('/puzzles', [PuzzlesController::class, 'getPuzzleList']);

Route::prefix('/workspace')->group(function ($router) {
    Route::post('/setup', [WorkspaceController::class, 'setup']);
    Route::post('/load', [WorkspaceController::class, 'load']);
    Route::get('/{file}', [WorkspaceController::class, 'getFile'])->where('file', '.*');
    Route::post('/{file}', [WorkspaceController::class, 'saveCodeFrame'])->where('file', '.*');
    Route::post('', [WorkspaceController::class, 'saveCodeFrames']);
});
