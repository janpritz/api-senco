<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Student, Payment};
use Illuminate\Http\Request;

class ReceiptsController extends Controller
{
    // app/Http/Controllers/Api/ReportSyncController.php
public function syncReports()
{
    return response()->json([
        'students' => Student::all(),
        'payments' => Payment::all()
    ]);
}
}
