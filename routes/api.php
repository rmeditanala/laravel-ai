<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAIController;

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

Route::post('/stream-chat', [OpenAIController::class, 'streamChat']);
Route::post('/chat', [OpenAIController::class, 'chat']);
