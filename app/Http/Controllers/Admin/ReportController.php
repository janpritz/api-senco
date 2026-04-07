<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Payment, Student};
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Get unique dates for the frontend dropdown.
     */
    public function getAvailableDates()
    {
        return Payment::select(DB::raw('DATE(created_at) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date');
    }

    /**
     * Get detailed report data for a specific date.
     */
    public function getReportData(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $date = $request->date;
        $colleges = ["CITE", "CASE", "CCJE", "COHME"];

        //Grand Total for the day
        // 1. Fetch all payments for this date once to avoid multiple queries
        // This ensures the "Grand Total" and "Transactions" are perfectly synced
        $allPaymentsForDate = Payment::with(['student', 'collector'])
            ->whereDate('created_at', $date)
            ->get();
        // 2. Calculate the Grand Total across ALL colleges for this date
        $grandTotalCollection = $allPaymentsForDate->sum('amount');

        $stats = collect($colleges)->map(function ($collegeName) use ($date) {

            // 1. Calculate total collected using a JOIN for database-level accuracy
            $totalCollected = Payment::join('students', 'payments.student_id', '=', 'students.student_id')
                ->where('students.college', $collegeName)
                ->whereDate('payments.created_at', $date)
                ->sum('payments.amount');

            // 2. Fetch students for this college with their lifetime payment sums
            $studentsInCollege = Student::where('college', $collegeName)
                ->withSum('payments', 'amount')
                ->get();

            // 3. Categorize students based on your balance logic
            $paidInFullCount = 0;
            $partialPaymentCount = 0;

            foreach ($studentsInCollege as $student) {
                $startingBalance = ($student->balance > 0) ? $student->balance : 4000;
                $totalPaid = $student->payments_sum_amount ?? 0;
                $remaining = $startingBalance - $totalPaid;

                if ($totalPaid > 0) {
                    if ($remaining <= 0) {
                        $paidInFullCount++;
                    } else {
                        $partialPaymentCount++;
                    }
                }
            }

            return [
                'college' => $collegeName,
                'total_collected' => (float)$totalCollected,
                'student_count' => $studentsInCollege->count(),
                'paid_in_full' => $paidInFullCount,
                'partial_payments' => $partialPaymentCount,
            ];
        });

        // 4. Detailed transaction list for the table
        $transactions = Payment::with(['student', 'collector'])
            ->whereDate('created_at', $date)
            ->get()
            ->map(fn($p) => [
                'reference_number' => $p->reference_number,
                'student_name' => $p->student->full_name ?? 'Unknown Student',
                'college' => $p->student->college ?? 'N/A',
                'amount' => (float)$p->amount,
                'collected_by' => $p->collector->name ?? 'System/Admin',
                'time' => $p->created_at->format('h:i A'),
            ]);

        // ADD THIS: Fetch all unique dates and their total sums for the Summary section
        $summaryData = Payment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(amount) as daily_total')
        )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $grandTotalAllTime = Payment::sum('amount');

        return response()->json([
            'selected_date' => $date,
            'grand_total' => (float)$allPaymentsForDate->sum('amount'), // Total for selected day
            'summary_list' => $summaryData, // List of all dates + amounts
            'overall_grand_total' => (float)$grandTotalAllTime, // Sum of everything ever
            'stats' => $stats,
            'transactions' => $transactions
        ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE);
    }

    // App\Http\Controllers\Admin\DashboardController.php or ReportController.php

    public function getAllTimeStats()
    {
        $colleges = ["CITE", "CASE", "CCJE", "COHME"];

        $stats = collect($colleges)->map(function ($collegeName) {
            // 1. Total lifetime collection for this college
            $totalCollected = Payment::whereHas('student', function ($q) use ($collegeName) {
                $q->where('college', $collegeName);
            })->sum('amount');

            // 2. Student population and their lifetime balance status
            $students = Student::where('college', $collegeName)
                ->withSum('payments', 'amount')
                ->get();

            $paidInFullCount = $students->filter(function ($student) {
                $startingBalance = ($student->balance > 0) ? $student->balance : 4000;
                return ($startingBalance - ($student->payments_sum_amount ?? 0)) <= 0;
            })->count();

            return [
                'college' => $collegeName,
                'total_collected' => (float)$totalCollected,
                'student_count' => $students->count(),
                'paid_in_full' => $paidInFullCount,
            ];
        });

        return response()->json([
            'overall_total' => (float)Payment::sum('amount'),
            'college_stats' => $stats
        ]);
    }
}
