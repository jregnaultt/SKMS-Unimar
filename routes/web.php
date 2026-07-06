<?php

use App\Http\Controllers\AssignedProductionController;
use App\Http\Controllers\BibliometricController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OaiPmhController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/oai', [OaiPmhController::class, 'index'])->name('oai');

Route::get('/bibliometrics', [BibliometricController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('bibliometrics.index');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::match(['QUERY', 'POST'], '/catalog/query', [CatalogController::class, 'search'])->name('catalog.query');
Route::get('/catalog/{uuid}', [CatalogController::class, 'showPublic'])->name('catalog.show-public');
Route::get('/catalog/{uuid}/document.pdf', [CatalogController::class, 'downloadPublicPdf'])->name('catalog.download-public-pdf');

use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/switch-role', [DashboardController::class, 'switchRole'])->name('dashboard.switch-role');
    Route::get('/assigned-productions', [AssignedProductionController::class, 'index'])
        ->name('assigned-productions.index')
        ->middleware('role:Tutor|Jurado|Coordinador|Decano');
});

use App\Http\Controllers\Admin\AdminAcademicPeriodController;
use App\Http\Controllers\Admin\AdminAcademicProgramController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminResearchLineController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BulkProductionController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProductionClaimController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/productions/create', [ProductionController::class, 'create'])->name('productions.create');
    Route::post('/productions/extract', [ProductionController::class, 'extractMetadata'])->name('productions.extract');
    Route::post('/productions', [ProductionController::class, 'store'])->name('productions.store');
    Route::get('/productions/{production}', [ProductionController::class, 'show'])->name('productions.show');
    Route::get('/productions/{production}/edit', [ProductionController::class, 'edit'])->name('productions.edit');
    Route::post('/productions/{production}/assign-users', [ProductionController::class, 'assignUsers'])->name('productions.assign-users');
    Route::get('/productions/{production}/documento', [ProductionController::class, 'downloadDocument'])->name('productions.document');
    Route::get('/versions/{version}/documento', [ProductionController::class, 'downloadVersionDocument'])->name('versions.document');
    Route::post('/productions/{production}/submit', [ProductionController::class, 'submitDraft'])->name('productions.submit-draft');
    Route::post('/productions/{production}/sync', [ProductionController::class, 'syncGoogleDoc'])->name('productions.sync');
    Route::post('/productions/{production}/request-jury-review', [ProductionController::class, 'requestJuryReview'])->name('productions.request-jury-review');
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

    // Progress tracking routes (Module 5)
    Route::get('/productions/{production}/progreso', [ProgressController::class, 'studentShow'])->name('progress.student.show');
    Route::get('/coordinacion/dashboard', [ProgressController::class, 'coordinatorIndex'])->name('progress.coordinator.index');
    Route::post('/productions/{production}/hitos', [ProgressController::class, 'configureMilestones'])->name('progress.milestones.store');

    // Notifications (Module 9)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    });

    // Admin panel routes (Module 10)
    Route::middleware(['role:Coordinador|Super Admin|Decano'])->prefix('admin')->name('admin.')->group(function () {
        // Bulk import routes
        Route::get('/productions/import', [BulkProductionController::class, 'index'])->name('productions.import');
        Route::post('/productions/import/upload', [BulkProductionController::class, 'upload'])->name('productions.import.upload');
        Route::get('/productions/import/status', [BulkProductionController::class, 'checkStatus'])->name('productions.import.status');
        Route::post('/productions/import/store', [BulkProductionController::class, 'storeBatch'])->name('productions.import.store');

        Route::resource('programs', AdminAcademicProgramController::class);
        Route::resource('lines', AdminResearchLineController::class);
        Route::get('students/search', [AdminAcademicPeriodController::class, 'searchStudents'])->name('students.search');
        Route::resource('periods', AdminAcademicPeriodController::class);
        Route::post('periods/{period}/tutors', [AdminAcademicPeriodController::class, 'storeTutor'])->name('periods.tutors.store');
        Route::delete('periods/{period}/tutors/{id}', [AdminAcademicPeriodController::class, 'destroyTutor'])->name('periods.tutors.destroy');
        Route::post('periods/{period}/enrollments', [AdminAcademicPeriodController::class, 'storeEnrollment'])->name('periods.enrollments.store');
        Route::delete('periods/{period}/enrollments/{enrollment}', [AdminAcademicPeriodController::class, 'destroyEnrollment'])->name('periods.enrollments.destroy');
        Route::post('periods/{period}/milestones', [AdminAcademicPeriodController::class, 'storeMilestone'])->name('periods.milestones.store');
        Route::delete('periods/{period}/milestones/{milestone}', [AdminAcademicPeriodController::class, 'destroyMilestone'])->name('periods.milestones.destroy');
        Route::get('periods/{period}/students-under-tutor', [AdminAcademicPeriodController::class, 'getStudentsUnderTutor'])->name('periods.students-under-tutor');
        Route::get('users/search', [AdminUserController::class, 'search'])->name('users.search');
        Route::resource('users', AdminUserController::class)->only(['index', 'edit', 'update']);
        Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AdminAuditLogController::class, 'show'])->name('audit-logs.show');

        // Reports (Module 8)
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('reports/download/{filename}', [ReportController::class, 'download'])->name('reports.download');
    });

    // Google Auth routes
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

require __DIR__.'/auth.php';
