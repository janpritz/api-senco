<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Student, Payment, ReceiptClaim};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceiptsController extends Controller
{
    public function claimReceipt(Request $request, $id)
    {
        $receiptClaim = ReceiptClaim::where('id', $id)->first();

        if (!$receiptClaim) {
            return response()->json(['message' => 'Receipt claim not found for this student.'], 404);
        }

        if ($receiptClaim->is_claimed) {
            return response()->json(['message' => 'This receipt has already been claimed.'], 400);
        }

        // Mark as claimed
        $adminName = Auth::id(); // Assuming you have authentication set up
        $receiptClaim->markAsClaimed($adminName);

        return response()->json(['message' => 'Receipt successfully claimed.']);
    }
    public function getStats()
    {
        try {
            // 1. Total records in the filing system
            $totalFilingCount = ReceiptClaim::count();

            // 2. Claim Status
            $claimedCount = ReceiptClaim::where('is_claimed', true)->count();
            $unclaimedCount = $totalFilingCount - $claimedCount;

            // 3. Export/Print Status
            $exportedCount = ReceiptClaim::where('is_exported', true)->count();
            $pendingExportCount = ReceiptClaim::where('is_exported', false)->count();

            // 4. College Breakdown (For physical folder organization)
            // Note: This assumes you join with students or have college in receipt_claims
            $collegeBreakdown = ReceiptClaim::join('students', 'receipt_claims.student_id', '=', 'students.student_id')
                ->select('students.college', DB::raw('count(*) as total'))
                ->groupBy('students.college')
                ->get();

            // 5. Calculate percentages for progress bars
            $claimRate = $totalFilingCount > 0
                ? round(($claimedCount / $totalFilingCount) * 100, 2)
                : 0;

            return response()->json([
                'summary' => [
                    'total_filing' => $totalFilingCount,
                    'claimed' => $claimedCount,
                    'unclaimed' => $unclaimedCount,
                    'exported' => $exportedCount,
                    'pending_export' => $pendingExportCount,
                    'claim_rate_percentage' => $claimRate,
                ],
                'by_college' => $collegeBreakdown,
                'recent_claims' => ReceiptClaim::where('is_claimed', true)
                    ->orderBy('claimed_at', 'desc')
                    ->limit(5)
                    ->get()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // app/Http/Controllers/Api/ReportSyncController.php
    public function syncReceipts()
    {
        return response()->json([
            'students' => Student::all(),
            'payments' => Payment::all(),
            'receipt_claims' => ReceiptClaim::all(),
        ]);
    }

    public function markAsExported(Request $request)
    {
        $updatedCount = ReceiptClaim::where('is_exported', false)
            ->where('updated_at', '<=', now())
            ->update(['is_exported' => true]);

        return response()->json([
            'success' => true,
            'message' => "Successfully marked $updatedCount fully paid students as exported.",
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    public function getPendingExport()
    {
        // Only get students who have reached 4k but HAVEN'T been exported yet
        return Student::whereHas('receiptClaim', function ($query) {
            $query->where('is_exported', false);
        })->with('receiptClaim')->get();
    }
}
