<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Cache, Log};

class DashboardController extends Controller
{
    // app/Http/Controllers/Admin/DashboardController.php
    public function index(Request $request)
    {
        // 1. Setup Constants
        $fee = (float) (DB::table('settings')->where('key', 'contribution_fee')->value('value') ?? 4300);
        $today = now()->timezone('Asia/Manila')->format('Y-m-d');

        // 2. Global Stats
        $totalStudents = Student::count();
        $totalCollected = (float) Payment::sum('amount');
        $dailyCollection = (float) Payment::whereDate('created_at', $today)->sum('amount');

        // 3. College Breakdown Logic
        // We use withSum to get the total paid per student, then group by college
        $studentsWithBalances = Student::query()
            ->withSum('payments', 'amount')
            ->get();

        //$zeroPayments = $studentsWithBalances->filter(fn($s) => ($s->payments_sum_amount ?? 0) <= 0)->count();

        $collegeStats = $studentsWithBalances->groupBy('college')->map(function ($students, $collegeName) use ($fee) {
            return [
                'college' => $collegeName,
                'total_students' => $students->count(),
                'fully_paid' => $students->filter(fn($s) => ($s->payments_sum_amount ?? 0) >= $fee)->count(),
                'zero_payments' => $students->filter(fn($s) => ($s->payments_sum_amount ?? 0) <= 0)->count(),
                'partial_payments' => $students->filter(fn($s) => ($s->payments_sum_amount > 0) && ($s->payments_sum_amount < $fee))->count(),
            ];
        })->values();

        // 4. Calculate Global Fully Paid (for the top cards)
        $totalFullyPaid = $collegeStats->sum('fully_paid');

        // 5. Get Collector Statistics
        $userTransactions = User::has('payments') // Only users who have collected
            ->withCount('payments')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'transactions_count' => $user->payments_count
                ];
            });

        return response()->json([
            'stats' => [
                'totalCollected' => $totalCollected,
                'dailyCollection' => $dailyCollection,
                'totalStudents' => $totalStudents,
                'fullyPaidStudents' => $totalFullyPaid,
                'expectedTotal' => $totalStudents * $fee,
                'zeroPaymentStudents' => $totalStudents - $totalFullyPaid,
                'userTransactions' => $userTransactions, // Add this line
            ],
            'college_breakdown' => $collegeStats,
            'contribution_fee' => $fee,
            'date_ref' => $today
        ]);
    }
}
