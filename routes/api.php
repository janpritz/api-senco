<?php

use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SyncGoogleSheetsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ViewTransactionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Public\StudentPortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Route
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
// This provides the name "password.setup" for the URL generator
// The GET route for the email button
Route::get('/password/setup/{user}', [AuthController::class, 'showPasswordSetupForm'])
    ->name('password.setup');

// The POST route for the actual form submission
Route::post('/password/setup/{user}', [AuthController::class, 'setupPassword'])
    ->name('password.update.signed');

Route::get('/password/verify/{user}', action: [AuthController::class, 'verifySignature'])
    ->name('password.verify');

Route::get('/student/records', [StudentPortalController::class, 'getRecords']);

// Protected Routes (Require Token)
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth route outside the admin prefix
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:10,1');

    // Main Admin Group
    Route::prefix('admin')->group(function () {

        // Dashboard Route
        Route::get('/dashboard-stats', [DashboardController::class, 'index']);

        // Student Route
        // GET /api/admin/students (List & Search)
        Route::get('/students', [StudentController::class, 'index']);

        // GET /api/admin/students/{student_id} (View Profile & History)
        Route::get('/students/{student_id}', [StudentController::class, 'show']);

        // POST /api/admin/students (Create/Add New Student)
        Route::post('/students', [StudentController::class, 'store']);

        // Payments Route
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/today', [CollectionController::class, 'getTodayContributions']);
        Route::patch('/payments/update-amount', [PaymentController::class, 'update']);
        Route::get('/payments/lookup', [PaymentController::class, 'lookup']);

        // User Management
        Route::post('/users/add', [UserController::class, 'store']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users/{user}/resend-invite', [UserController::class, 'resendInvite']);
        Route::post('/users/{user}/suspend', [UserController::class, 'suspend']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        // Collection Routes
        Route::get('/masterlist', [CollectionController::class, 'index']);
        Route::get('/students/search/{studentId}', [CollectionController::class, 'show']);
        Route::get('/sync-google-sheets', [SyncGoogleSheetsController::class, 'syncGoogleSheets']);

        //Get the transactions
        Route::get('/transactions', [ViewTransactionController::class, 'index']);
        Route::get('/transactions/user', [ViewTransactionController::class, 'getTransactions']);

        // Nested Settings Group (Resulting URL: /admin/settings)
        Route::resource('settings', SettingController::class)->except(['create', 'edit']);
    });
});
