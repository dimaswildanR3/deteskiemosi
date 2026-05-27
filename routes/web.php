<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BasicController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\DetectionController;
// use App\Http\Controllers\DetectionController;

Route::get('/start-detection', [DetectionController::class, 'start']);
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =====================
// PUBLIC
// =====================
Route::get('/', function () {
    return view('auth.login'); // atau redirect
});



Route::get('/kamera', [DetectionController::class, 'index']);
Route::post('/deteksi', [DetectionController::class, 'prosesDeteksi'])->name('deteksi.proses');

Route::post('/start-detection', [DetectionController::class, 'start'])
    ->name('start.detection');

 Route::get('/clear-data', 'SessionController@clearMyData')
    ->middleware('auth')
    ->name('clear.data');
// =====================

// AUTH REQUIRED
// =====================
Route::middleware('auth')->group(function () {
    Route::get(
        '/monitoring/export',
        'SessionController@exportExcel'
    )->name('monitoring.export');
    Route::get('/monitoring/report', 'SessionController@report')
    ->name('monitoring.report');

    // Dashboard
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Logout
    Route::post('/logout', [HomeController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Classes
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes/store', [ClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/edit/{id}', [ClassController::class, 'edit'])->name('classes.edit');
    Route::post('/classes/update/{id}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/delete/{id}', [ClassController::class, 'destroy'])->name('classes.destroy');

    // Monitoring
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/view/{id}', [HomeController::class, 'view'])->name('monitoring.view');

    // Basic (Resource)
    Route::resource('basic', BasicController::class);

});

// =====================
// OPTIONAL PAGES
// =====================
Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/blank', function () {
    return view('blank');
})->name('blank');