<?php

namespace App\Services\Queue;

use App\Models\Queue;
use Illuminate\Support\Facades\DB;

class QueueService
{
    /**
     * Create a new class instance.
     */
    public function prioritizeStudent($id)
    {
        return DB::transaction(function () use ($id) {
            $item = Queue::findOrFail($id);
            
            // Set to 1 (Priority)
            $item->update(['priority_group' => 1]);

            return $item;
        });
    }
}
