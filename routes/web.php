<?php

use App\Http\Controllers\KomplainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/komplain', [KomplainController::class, 'index'])->name('komplain.index');
Route::get('/komplain/status', [KomplainController::class, 'getKomplainStatus'])->name('komplain.status');
Route::get('/komplain/detail-status', [KomplainController::class, 'getDetailStatus']);
Route::get('/komplain/total-unit', [KomplainController::class, 'getTotalUnit']);
// Route::get('/komplain/detail-unit', [KomplainController::class, 'getDetailUnit']);
Route::get('/komplain/petugas', [KomplainController::class, 'getPetugas']);
