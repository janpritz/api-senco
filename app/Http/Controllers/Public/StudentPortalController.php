<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPortalController extends Controller
{
    public function getRecords(Request $request)
    {
        $studentId = $request->query('student_id');
        $portalCode = $request->query('portal_code');
        $key = $request->query('key');

        if (!$key || $key !== env('STUDENT_PORTAL_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $student = Student::where('student_id', $studentId)->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'error' => 'Student ID not found in our records.'
            ], 404);
        }

        // 2. Student exists, now verify the Portal Code
        // We compare the portal_code for this specific student
        if ($student->portal_code !== $portalCode) {
            return response()->json([
                'status' => 'error',
                'error' => 'Invalid portal code. Please check credentials provided in your email and try again.'
            ], 403); // 403 Forbidden is more appropriate for credential errors
        }

        $student->load(['payments.collector']);
        $totalPaid = (float) $student->payments->sum('amount');
        $fee = (float) (DB::table('settings')->where('key', 'contribution_fee')->value('value') ?? 4300);

        return response()->json([
            'status'         => 'success',
            'student_id'     => $student->student_id,
            'name'   => $student->full_name, // Map to name in frontend if needed
            'total_paid'     => $totalPaid,
            'balance'        => max(0, $fee - $totalPaid),
            'account_status'         => $totalPaid >= $fee ? 'Fully Paid' : 'Partially Paid',
            'history'        => $student->payments->map(function ($p) {
                return [
                    'amount'       => $p->amount,
                    'reference'    => $p->id,
                    'collected_by' => $p->collector->name ?? 'System',
                    'date'         => $p->created_at->format('M d, Y h:i A'),
                ];
            }),
        ]);
    }
}
