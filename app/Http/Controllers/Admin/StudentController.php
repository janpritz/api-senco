<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // app/Http/Controllers/Api/Admin/StudentController.php

    public function index(Request $request)
    {
        $query = Student::query();

        // CRITICAL: This provides the 'payments_sum_amount' to the model
        $query->withSum('payments', 'amount');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // The 'remaining_balance' is automatically added by the Model's $appends
        return response()->json($query->orderBy('last_name')->get());
    }
}
