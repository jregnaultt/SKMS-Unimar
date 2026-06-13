<?php

use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\ProductionClaimController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/productions/create', [ProductionController::class, 'create'])->name('productions.create');
    Route::post('/productions/extract', [ProductionController::class, 'extractMetadata'])->name('productions.extract');

    // Claims routes
    Route::post('/claims', [ProductionClaimController::class, 'store'])->name('claims.store');
    Route::get('/admin/claims', [ProductionClaimController::class, 'index'])->name('admin.claims.index');
    Route::post('/admin/claims/{claim}/approve', [ProductionClaimController::class, 'approve'])->name('admin.claims.approve');
    Route::post('/admin/claims/{claim}/reject', [ProductionClaimController::class, 'reject'])->name('admin.claims.reject');
});

require __DIR__.'/auth.php';
