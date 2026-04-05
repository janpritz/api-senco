<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function recordPayment($data, $adminId)
    {
        return DB::transaction(function () use ($data, $adminId) {
            // 1. Calculate the installment number (01, 02, 03)
            $installmentCount = DB::table('payments')
                ->where('student_id', $data['student_id'])
                ->count();
            $installmentPrefix = str_pad($installmentCount + 1, 2, '0', STR_PAD_LEFT);

            // 2. Insert the record FIRST with a placeholder
            // This reserves the unique Auto-Increment ID from MySQL
            $payment = Payment::create([
                'student_id'       => $data['student_id'],
                'amount'           => $data['amount'],
                'collected_by'     => $adminId,
                'reference_number' => 'TEMP-' . Str::random(10), // Temporary
            ]);

            // 3. Now use the actual Database ID to create the 6-digit suffix
            // This guarantees NO DUPLICATES because the ID is unique
            $incrementalId = str_pad($payment->id, 6, '0', STR_PAD_LEFT);
            $finalReference = "SENCO-{$installmentPrefix}-{$incrementalId}";

            // 4. Update the record with the final reference
            $payment->update([
                'reference_number' => $finalReference
            ]);

            return $payment;
        });
    }
}
