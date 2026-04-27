<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{CollectionReport, Payment, Student, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Get unique dates for dropdown
     */

    public function index()
    {
        // 1. Get all narrative reports with the user who wrote them
        $reports = CollectionReport::with('user:id,name')
            ->orderBy('report_date', 'desc')
            ->get();

        // 2. Get a list of users who have actually collected payments today
        // We assume you have a 'payments' or 'collections' relationship on the User model
        $collectorsToday = User::whereHas('payments', function ($query) {
            $query->whereDate('created_at', Carbon::today());
        })->select('id', 'name')->get();

        return response()->json([
            'reports' => $reports,
            'collectors_today' => $collectorsToday
        ]);
    }

    public function storeDailyNarrative(Request $request)
    {
        $request->validate([
            'summary' => 'required|string',
            'total_collected' => 'required|numeric'
        ]);

        $report = CollectionReport::updateOrCreate(
            [
                'report_date' => now()->format('Y-m-d'),
                'user_id' => Auth::id()
            ],
            [
                'total_collected' => $request->total_collected,
                'transaction_count' => $request->transaction_count,
                'summary' => $request->summary,
                'issues' => $request->issues,
                'resolutions' => $request->resolutions,
                'status' => 'submitted'
            ]
        );

        return response()->json([
            'message' => 'Narrative report synced successfully',
            'report' => $report
        ]);
    }
    public function getAvailableDates()
    {
        return Payment::select(DB::raw('DATE(created_at) as date'))
            ->distinct()
            ->orderBy('date', 'asc')
            ->pluck('date');
    }

    /**
     * Main report data (CUMULATIVE LOGIC)
     */
    public function getReportData(Request $request)
    {
        $request->validate([
            'date' => 'required|string'
        ]);

        $date = $request->date;
        $isAllTime = ($date === 'all');
        $colleges = ["CITE", "CASE", "CCJE", "COHME"];
        $target = 4000;

        // ===============================
        // 1. TRANSACTIONS (ACCUMULATED)
        // ===============================
        $query = Payment::with(['student', 'collector']);

        if (!$isAllTime) {
            $query->whereDate('created_at', '<=', $date);
        }

        $allPayments = $query->get();

        $transactions = $allPayments->map(function ($p) {
            return [
                'reference_number' => $p->reference_number,
                'student_name' => $p->student->full_name ?? 'Unknown Student',
                'college' => $p->student->college ?? 'N/A',
                'amount' => (float) $p->amount,
                'collected_by' => $p->collector->name ?? 'System/Admin',
                'time' => $p->created_at->format('M d, Y h:i A'),
            ];
        });

        // ===============================
        // 2. COLLEGE STATS (ACCUMULATED)
        // ===============================
        $stats = collect($colleges)->map(function ($collegeName) use ($date, $isAllTime, $target) {

            // Base query per college
            $basePaymentQuery = Payment::join('students', 'payments.student_id', '=', 'students.student_id')
                ->where('students.college', $collegeName);

            if (!$isAllTime) {
                $basePaymentQuery->whereDate('payments.created_at', '<=', $date);
            }

            // Total collected (ACCUMULATED)
            $totalCollected = $basePaymentQuery->sum('payments.amount');

            // Total students
            $studentCount = Student::where('college', $collegeName)->count();

            // Paid in full (ACCUMULATED)
            $paidInFullCount = Student::where('college', $collegeName)
                ->whereIn('student_id', function ($query) use ($collegeName, $date, $isAllTime, $target) {
                    $query->select('payments.student_id')
                        ->from('payments')
                        ->join('students', 'payments.student_id', '=', 'students.student_id')
                        ->where('students.college', $collegeName)
                        ->when(!$isAllTime, function ($q) use ($date) {
                            $q->whereDate('payments.created_at', '<=', $date);
                        })
                        ->groupBy('payments.student_id')
                        ->havingRaw('SUM(payments.amount) >= ?', [$target]);
                })
                ->count();

            // Partial payments (ACCUMULATED)
            $partialPaymentCount = Student::where('college', $collegeName)
                ->whereIn('student_id', function ($query) use ($collegeName, $date, $isAllTime, $target) {
                    $query->select('payments.student_id')
                        ->from('payments')
                        ->join('students', 'payments.student_id', '=', 'students.student_id')
                        ->where('students.college', $collegeName)
                        ->when(!$isAllTime, function ($q) use ($date) {
                            $q->whereDate('payments.created_at', '<=', $date);
                        })
                        ->groupBy('payments.student_id')
                        ->havingRaw('SUM(payments.amount) > 0 AND SUM(payments.amount) < ?', [$target]);
                })
                ->count();

            return [
                'college' => $collegeName,
                'total_collected' => (float) $totalCollected,
                'student_count' => $studentCount,
                'paid_in_full' => $paidInFullCount,
                'partial_payments' => $partialPaymentCount,
            ];
        });

        // ===============================
        // 3. SUMMARY LIST (UP TO DATE)
        // ===============================
        $summaryData = Payment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(amount) as daily_total')
        )
            ->when(!$isAllTime, function ($q) use ($date) {
                $q->whereDate('created_at', '<=', $date);
            })
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // ===============================
        // 4. GRAND TOTAL (ACCUMULATED)
        // ===============================
        $grandTotalQuery = Payment::query();

        if (!$isAllTime) {
            $grandTotalQuery->whereDate('created_at', '<=', $date);
        }

        $overallGrandTotal = $grandTotalQuery->sum('amount');

        // ===============================
        // RESPONSE
        // ===============================
        return response()->json([
            'selected_date' => $isAllTime ? 'All-Time' : $date,
            'report_total' => (float) $allPayments->sum('amount'),
            'summary_list' => $summaryData,
            'overall_grand_total' => (float) $overallGrandTotal,
            'stats' => $stats,
            'transactions' => $transactions
        ]);
    }

    /**
     * All-time stats (UNCHANGED)
     */
    public function getAllTimeStats()
    {
        $colleges = ["CITE", "CASE", "CCJE", "COHME"];
        $target = 4000;

        $stats = collect($colleges)->map(function ($collegeName) use ($target) {

            $totalCollected = Payment::join('students', 'payments.student_id', '=', 'students.student_id')
                ->where('students.college', $collegeName)
                ->sum('payments.amount');

            $studentCount = Student::where('college', $collegeName)->count();

            $paidInFullCount = Student::where('college', $collegeName)
                ->whereIn('student_id', function ($query) use ($target) {
                    $query->select('student_id')
                        ->from('payments')
                        ->groupBy('student_id')
                        ->havingRaw('SUM(amount) >= ?', [$target]);
                })
                ->count();

            $partialPaymentCount = Student::where('college', $collegeName)
                ->whereIn('student_id', function ($query) use ($target) {
                    $query->select('student_id')
                        ->from('payments')
                        ->groupBy('student_id')
                        ->havingRaw('SUM(amount) > 0 AND SUM(amount) < ?', [$target]);
                })
                ->count();

            return [
                'college' => $collegeName,
                'total_collected' => (float) $totalCollected,
                'student_count' => $studentCount,
                'paid_in_full' => $paidInFullCount,
                'partial_payments' => $partialPaymentCount,
            ];
        });

        return response()->json([
            'overall_total' => (float) Payment::sum('amount'),
            'college_stats' => $stats
        ]);
    }
}
