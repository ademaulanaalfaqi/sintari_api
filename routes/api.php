<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SintariController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/',function(){
    return response()->json([
        'status'=>false,
        'message'=>'tak boleh akses'
    ],401);
})->name('login');
Route::post('registerUser',[AuthController::class,'registerUser']);
Route::post('loginUser',[AuthController::class,'loginUser']);
Route::get('sintari',[SintariController::class,'index'])->middleware('auth:sanctum');
Route::get('logout',[AuthController::class,'logout']);
