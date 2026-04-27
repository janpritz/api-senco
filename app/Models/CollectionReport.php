<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'total_collected',
        'transaction_count',
        'summary',
        'issues',
        'resolutions',
        'status',
        'user_id'
    ];

    /**
     * Casts for specific types
     */
    protected $casts = [
        'report_date' => 'date',
        'total_collected' => 'decimal:2',
    ];

    /**
     * Get the user who created the report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}