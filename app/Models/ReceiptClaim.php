<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptClaim extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'is_claimed',
        'is_exported',
        'released_by',
        'claimed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_claimed' => 'boolean',
        'claimed_at' => 'datetime',
    ];

    /**
     * Scope a query to only include unclaimed receipts.
     */
    public function scopeUnclaimed($query)
    {
        return $query->where('is_claimed', false);
    }

    /**
     * Mark the receipt as claimed.
     * 
     * @param string $adminName
     * @return bool
     */
    public function markAsClaimed(string $adminName)
    {
        return $this->update([
            'is_claimed' => true,
            'released_by' => $adminName,
            'claimed_at' => now(),
        ]);
    }
}