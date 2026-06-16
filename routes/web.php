<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkflowController;
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
    Route::post('/productions', [ProductionController::class, 'store'])->name('productions.store');
    Route::get('/productions/{production}', [ProductionController::class, 'show'])->name('productions.show');
    Route::get('/productions/{production}/documento', [ProductionController::class, 'downloadDocument'])->name('productions.document');
    Route::get('/versions/{version}/documento', [ProductionController::class, 'downloadVersionDocument'])->name('versions.document');
    Route::post('/productions/{production}/submit', [ProductionController::class, 'submitDraft'])->name('productions.submit-draft');
    Route::delete('/productions/{production}', [ProductionController::class, 'destroy'])->name('productions.destroy');
    Route::post('/productions/{production}/transition', [WorkflowController::class, 'transition'])->name('productions.transition');

    // Claims routes
    Route::post('/claims', [ProductionClaimController::class, 'store'])->name('claims.store');
    Route::get('/admin/claims', [ProductionClaimController::class, 'index'])->name('admin.claims.index');
    Route::post('/admin/claims/{claim}/approve', [ProductionClaimController::class, 'approve'])->name('admin.claims.approve');
    Route::post('/admin/claims/{claim}/reject', [ProductionClaimController::class, 'reject'])->name('admin.claims.reject');

    // Comment / Feedback routes (Module 4)
    Route::post('/productions/{production}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/comments/{comment}/reply', [CommentController::class, 'reply'])->name('comments.reply');
    Route::patch('/comments/{comment}/status', [CommentController::class, 'updateStatus'])->name('comments.update-status');
    Route::post('/comments/{comment}/verify', [CommentController::class, 'verify'])->name('comments.verify');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

require __DIR__.'/auth.php';
