<?php

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

Route::group([
    'middleware' => ['guest.uuid', 'cors'],
], function ($router) {
    Route::post('/workspace/setup', [WorkspaceController::class, 'setup']);
    Route::post('/workspace/load', [WorkspaceController::class, 'load']);
    Route::get('/workspace/{file}', [WorkspaceController::class, 'getFile'])
        ->where('file', '.*');
    Route::post('/workspace/{file}', [WorkspaceController::class, 'saveCodeFrame'])
        ->where('file', '.*');
    Route::post('/workspace', [WorkspaceController::class, 'saveCodeFrames']);
});
