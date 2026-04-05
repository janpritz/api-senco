<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Student.php

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'email',
        'college',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'portal_code',
    ];

    // This adds 'full_name' to the JSON response automatically
    protected $appends = ['full_name', 'remaining_balance'];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} " .
            ($this->middle_name ? $this->middle_name . ' ' : '') .
            "{$this->last_name}" .
            ($this->suffix ? ' ' . $this->suffix : '');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'student_id', 'student_id');
    }

    public function getRemainingBalanceAttribute()
    {
        // 1. Determine the starting balance (Default to 4000 if 0 or null)
        $startingBalance = ($this->balance > 0) ? $this->balance : 4000;

        // 2. Get the sum of payments (provided by withSum in the controller)
        $totalPaid = $this->payments_sum_amount ?? 0;

        // 3. Return the calculated remainder
        return max(0, $startingBalance - $totalPaid);
    }
}
