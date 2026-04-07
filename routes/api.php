<?php
use Illuminate\Support\Facades\Route;

// Version 1 Routes
Route::prefix('v1')->group(base_path('routes/versions/api_v1.php'));

// Version 2 Routes (When you're ready to launch breaking changes)
Route::prefix('v2')->group(base_path('routes/versions/api_v2.php'));