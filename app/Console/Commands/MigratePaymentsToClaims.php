<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\ReceiptClaim;
use Illuminate\Support\Facades\DB;

class MigratePaymentsToClaims extends Command
{
    // Adding a flag for flexibility (total vs exact)
    protected $signature = 'senco:migrate-qualified {--total=4000}';
    protected $description = 'Generate receipt claims only for students who reached the target amount';

    public function handle()
    {
        $targetAmount = $this->option('total');
        $this->info("Filtering students who have paid a total of {$targetAmount}...");

        // 1. Fetch students whose SUM of payments equals or exceeds the target
        $qualifiedStudents = Payment::select('student_id', 
                DB::raw('SUM(amount) as total_paid'), 
                DB::raw('MIN(created_at) as first_payment'))
            ->groupBy('student_id')
            ->having('total_paid', '>=', $targetAmount) 
            ->orderBy('first_payment', 'asc')
            ->get();

        if ($qualifiedStudents->isEmpty()) {
            $this->warn("No students found with a total payment of {$targetAmount}.");
            return;
        }

        $count = 0;
        foreach ($qualifiedStudents as $row) {
            // 2. Lock them into the receipt_claims table
            $claim = ReceiptClaim::firstOrCreate(
                ['student_id' => $row->student_id],
                [
                    'is_claimed' => false,
                    'is_exported' => false,
                    'released_by' => null,
                    'claimed_at' => null,
                    'created_at' => $row->first_payment,
                ]
            );

            if ($claim->wasRecentlyCreated) {
                $count++;
            }
        }

        $this->info("Successfully generated {$count} new records for the physical filing system.");
    }
}