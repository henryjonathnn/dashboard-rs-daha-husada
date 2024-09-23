<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KomplainController;
use App\Http\Controllers\UpdateController;
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

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/komplain', [KomplainController::class, 'index']);
Route::get('/komplain/data-unit', [KomplainController::class, 'dataUnit']);
Route::get('/komplain/data-kinerja', [KomplainController::class, 'dataKinerja']);
Route::get('/komplain/status', [KomplainController::class, 'getKomplainStatus']);
Route::get('/komplain/detail-status', [KomplainController::class, 'getDetailStatus']);
Route::get('/komplain/total-unit', [KomplainController::class, 'getTotalUnit']);
Route::get('/komplain/detail-unit', [KomplainController::class, 'getDetailUnit']);
Route::get('/komplain/petugas', [KomplainController::class, 'getPetugas']);

Route::get('/permintaan-update', [UpdateController::class, 'index']);
Route::get('/permintaan-update/data-kinerja', [UpdateController::class, 'dataKinerja']);
Route::get('/permintaan-update/status', [UpdateController::class, 'getKomplainStatus']);
Route::get('/permintaan-update/detail-status', [UpdateController::class, 'getDetailStatus']);
Route::get('/permintaan-update/petugas', [UpdateController::class, 'getPetugas']);
