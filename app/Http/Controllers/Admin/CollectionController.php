<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Log, Http, DB};

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        // 1. Fetch the global fee
        $fee = (float) (DB::table('settings')->where('key', 'contribution_fee')->value('value') ?? 4300);

        // 2. Fetch all students and join their total payments
        // We use a LEFT JOIN so students with 0 payments still show up
        $students = DB::table('students')
            ->leftJoin('payments', 'students.student_id', '=', 'payments.student_id')
            ->select(
                'students.student_id',
                'students.first_name',
                'students.middle_name',
                'students.last_name',
                'students.suffix',
                'students.college',
                DB::raw('COALESCE(SUM(payments.amount), 0) as total_paid')
            )
            ->groupBy(
                'students.student_id',
                'students.first_name',
                'students.middle_name',
                'students.last_name',
                'students.suffix',
                'students.college'
            )
            ->get()
            ->map(function ($student) use ($fee) {
                return [
                    'student_id' => $student->student_id,
                    'full_name'  => trim(sprintf(
                        '%s %s %s %s',
                        $student->first_name,
                        $student->middle_name,
                        $student->last_name,
                        $student->suffix
                    )),
                    'college'    => $student->college,
                    'total_paid' => (float) $student->total_paid,
                    'balance'    => $fee - (float) $student->total_paid,
                ];
            });

        return response()->json($students);
    }
    public function show($studentId)
    {
        $student = DB::table('students')
            ->where('student_id', $studentId)
            ->first();

        if (!$student) {
            return response()->json(['message' => "Student ID $studentId not found."], 404);
        }

        // 1. Fetch payment history from DB
        $payments = DB::table('payments')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        $fee = (float) (DB::table('settings')->where('key', 'contribution_fee')->value('value') ?? 4300);
        $totalPaid = $payments->sum('amount');
        $currentBalance = $fee - $totalPaid;

        return response()->json([
            'student_id'      => $student->student_id,
            'full_name'       => trim(sprintf(
                '%s %s %s %s',
                $student->first_name,
                $student->middle_name,
                $student->last_name,
                $student->suffix
            )),
            'college'         => $student->college ?? 'N/A',
            'course'          => $student->course ?? 'N/A',
            'balance'         => $currentBalance,
            'payment_history' => $payments,
            'is_cleared'      => $currentBalance <= 0
        ]);
    }

    public function getTodayContributions()
    {
        $today = now()->today();

        // Use a Join to get the full_name directly from the students table
        // This is much faster and more reliable than hitting the Cache for every row
        $payments = DB::table('payments')
            ->join('students', 'payments.student_id', '=', 'students.student_id')
            ->whereDate('payments.created_at', $today)
            ->select(
                'payments.id',
                'payments.student_id',
                'payments.amount',
                'payments.created_at',
                // Check if reference_no exists, otherwise use ID as fallback
                DB::raw('COALESCE(payments.reference_number, CONCAT("REF-", payments.id)) as ref'),
                'students.first_name',
                'students.middle_name',
                'students.last_name',
                'students.suffix'
            )
            ->orderBy('payments.created_at', 'desc')
            ->get();

        $formatted = $payments->map(function ($payment) {
            // Construct the full name from the joined student columns
            $fullName = trim("{$payment->first_name} " .
                ($payment->middle_name ? $payment->middle_name . ' ' : '') .
                "{$payment->last_name} " .
                ($payment->suffix ?? ''));

            return [
                'id' => $payment->id,
                'student_id' => $payment->student_id,
                'full_name' => $fullName ?: 'Unknown Student',
                'amount' => (float)$payment->amount,
                'reference_no' => $payment->ref, // Uses the aliased COALESCE from SQL
                'time' => date('h:i A', strtotime($payment->created_at)),
            ];
        });

        return response()->json($formatted);
    }
}
