<?php

use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SyncGoogleSheetsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ViewTransactionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Public\StudentPortalController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\ReceiptsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Public Routes ---
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/password/setup/{user}', [AuthController::class, 'showPasswordSetupForm'])->name('password.setup');
Route::post('/password/setup/{user}', [AuthController::class, 'setupPassword'])->name('password.update.signed');
Route::get('/password/verify/{user}', [AuthController::class, 'verifySignature'])->name('password.verify');
Route::get('/student/records', [StudentPortalController::class, 'getRecords']);

// QUEUE ROUTE PUBLIC
Route::get('/queue/status', [QueueController::class, 'getStatus']);

// --- Protected Routes ---
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/users', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:10,1');

    Route::prefix('admin')->group(function () {

        /**
         * 1. ADMIN ONLY (Super Admin)
         * Access: User Management, Settings, Masterlist Sync
         */

        Route::middleware(['role:Admin'])->group(function () {
            // User Management
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users/add', [UserController::class, 'store']);
            Route::post('/users/{user}/resend-invite', [UserController::class, 'resendInvite']);
            Route::post('/users/{user}/suspend', [UserController::class, 'suspend']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);

            // System Settings & Sync
            Route::resource('settings', SettingController::class)->except(['create', 'edit']);

            // Student Management (Full Control)
            Route::post('/students', [StudentController::class, 'store']);

            // Reports
            Route::prefix('reports')->group(function () {
                Route::get('/dates', [ReportController::class, 'getAvailableDates']);
                Route::get('/generate', [ReportController::class, 'getReportData']);
            });
        });

        /**
         * 2. AUDITOR & ADMIN
         * Access: Create/
         */
        Route::middleware(['role:Admin,Auditor'])->group(function () {
            Route::post('/payments', [PaymentController::class, 'store']);
            Route::get('/sync-google-sheets', [SyncGoogleSheetsController::class, 'syncGoogleSheets']);
        });

        // Update Payments
        Route::middleware(['role:Admin'])->group(function () {
            Route::patch('/payments/update-amount', [PaymentController::class, 'update']);
        });

        /**
         * 3. ALL ROLES (Admin, Auditor, Adviser)
         * Access: View-only Dashboard, Collections, Payments, and Transactions
         */
        Route::middleware(['role:Admin,Auditor,Adviser'])->group(function () {
            // Dashboard
            Route::get('/dashboard-stats', [DashboardController::class, 'index']);

            // Collections & Masterlist (View Only)
            Route::get('/masterlist', [CollectionController::class, 'index']);
            Route::get('/students', [StudentController::class, 'index']);
            Route::get('/students/{student_id}', [StudentController::class, 'show']);
            Route::get('/students/search/{studentId}', [CollectionController::class, 'show']);

            // Payments & Transactions (View Only)
            Route::get('/payments', [PaymentController::class, 'index']);
            Route::get('/payments/today', [CollectionController::class, 'getTodayContributions']);
            Route::get('/payments/lookup', [PaymentController::class, 'lookup']);
            Route::get('/transactions', [ViewTransactionController::class, 'index']);
            Route::get('/transactions/user', [ViewTransactionController::class, 'getTransactions']);
            Route::get('/reports/all-time-stats', [ReportController::class, 'getAllTimeStats']);
            Route::get('/reports', [ReportController::class, 'index']);

            // Receipts
            Route::prefix('receipts')->group(function () {
                // Get students who are ready for export (e.g., reached 4k but not exported)
                Route::get('/pending-export', [ReceiptsController::class, 'getPendingExport']);

                // Bulk update is_exported when the "Download PDF" button is clicked
                Route::post('/mark-exported', [ReceiptsController::class, 'markAsExported']);

                // Individual claim (When a student physically picks up the receipt)
                Route::post('/{id}/claim', [ReceiptsController::class, 'claimReceipt']);

                // Sync/Register batch (For the "Lock-in" filing ID logic)
                Route::get('/sync', [ReceiptsController::class, 'syncReceipts']);

                // Stats for your Dashboard (Total claimed vs unclaimed)
                Route::get('/stats', [ReceiptsController::class, 'getStats']);

                // routes/api.php
                Route::get('/check-exports', [ReceiptsController::class, 'checkExports']);
            });
        });


        Route::prefix('queue')->group(function () {
            // Both Admins and Staff can register students (Attendance Desk)
            Route::middleware(['role:Admin,Staff', 'throttle:40,1'])->group(function () {
                Route::post('/register', [QueueController::class, 'register']);   // For Attendance
                //Route::post('/next', [QueueController::class, 'triggerNext']);           // Admin Button
                //Route::post('/back', [QueueController::class, 'triggerBack']);           // Admin Button
                Route::post('/complete', [QueueController::class, 'complete']);   // Admin Button
                Route::get('/status', [QueueController::class, 'getStatus']);   // For Display
                // This matches /admin/queue/next_toga, /admin/queue/next_creative, etc.
                Route::post('/{action}', [QueueController::class, 'handleAction']);
            });
        });
    });
});
