<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentStoreRequest;
use App\Models\Payment;
use App\Services\Admin\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, DB, Log};
use App\Jobs\SendPaymentReceiptJob;
use App\Jobs\SendPaymentUpdateReceiptJob;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(PaymentStoreRequest $request)
    {
        $validated = $request->validated();

        try {
            // The Service Layer handles the SENCO-XX-XXXXXX logic
            $payment = $this->paymentService->recordPayment($validated, Auth::id());

            // Dispatch the email job after successful payment recording
            SendPaymentReceiptJob::dispatch($payment);
            return response()->json([
                'id'               => $payment->id,
                'student_id'       => $payment->student_id,
                'amount'           => (float) $payment->amount,
                'reference_number' => $payment->reference_number,
                'time'             => $payment->created_at->format('h:i A'),
                'date'             => $payment->created_at->toDateString(),
                'full_name'        => $request->full_name ?? 'Student',
                'message'          => 'Payment recorded successfully. A receipt email has been sent to the student.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function index()
    {
        $payments = Payment::with('student')
            ->latest()
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'student_id' => $payment->student_id,
                    'full_name' => $payment->student->full_name ?? 'Unknown Student',
                    'amount' => (float)$payment->amount,
                    'college' => $payment->student->college ?? 'Unknown College',
                    // Ensure we use the exact DB column name here
                    'reference_number' => $payment->reference_number ?? 'REF-' . $payment->id,
                    // Send ISO 8601 string so JavaScript can parse it easily
                    'created_at' => $payment->created_at->toIso8601String(),
                    'collected_by_name' => $payment->collector->name ?? 'System',
                ];
            });

        return response()->json($payments);
    }

    public function update(Request $request)
    {
        $request->validate([
            'reference_number' => 'required|string|exists:payments,reference_number',
            'amount'           => 'required|numeric|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $payment = Payment::where('reference_number', $request->reference_number)->firstOrFail();
            $studentId = $payment->student_id;
            $goal = 4000;

            // Calculate what the total WOULD be if we allow this update
            // (Current Total - Old Amount + New Proposed Amount)
            $currentTotal = Payment::where('student_id', $studentId)->sum('amount');
            $projectedTotal = ($currentTotal - $payment->getOriginal('amount')) + $request->amount;

            if ($projectedTotal > $goal) {
                $maxAllowed = $goal - ($currentTotal - $payment->getOriginal('amount'));

                return response()->json([
                    'status' => 'error',
                    'message' => "Update denied. This would bring the student's total to ₱" . number_format($projectedTotal, 2) . ". The maximum amount allowed for this specific transaction is ₱" . number_format($maxAllowed, 2) . "."
                ], 422); // 422 Unprocessable Content
            }

            $payment->update(['amount' => $request->amount]);

            // Re-dispatch the receipt so they see they are now "Fully Paid"
            SendPaymentUpdateReceiptJob::dispatch($payment); // Pass 'true' to indicate this is an update email

            return response()->json(['status' => 'success', 'message' => 'Payment updated.']);
        });
    }

    public function lookup(Request $request)
    {
        //Reject if request is not admin

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $reference = strtoupper(trim($request->query('ref')));

        $payment = Payment::whereRaw('UPPER(reference_number) = ?', [$reference])->first();

        if (!$payment) return response()->json(['message' => 'Not found'], 404);

        return response()->json([
            'amount' => $payment->amount,
            'student' => $payment->student->full_name
        ]);
    }
}
