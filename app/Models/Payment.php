<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'reference_number',
        'amount',
        'collected_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Get the formatted amount with Peso sign.
     * Use: $payment->formatted_amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₱' . number_format($this->amount, 2);
    }

    /**
     * Scope a query to only include payments made today.
     * Use: Payment::today()->get();
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Define relationship to Student model.
     * Assumes 'student_id' in payments table corresponds to 'student_id' in students table.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // app/Models/Payment.php
    public function collector()
    {
        // Points to the User who collected the payment
        return $this->belongsTo(User::class, 'collected_by');
    }
}
