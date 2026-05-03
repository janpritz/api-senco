<?php

namespace App\Services\Admin;

use App\Models\ReceiptClaim;

class ReceiptClaimService
{
    public function updateOrCreateFiling(string $studentId)
    {
        // We use firstOrCreate so the 'id' (Filing Number) is locked 
        // the very first time they pay and never changes.
        return ReceiptClaim::firstOrCreate(
            ['student_id' => $studentId],
            [
                'is_claimed' => false,
                'released_by' => null,
                'claimed_at' => null,
            ]
        );
    }
}
