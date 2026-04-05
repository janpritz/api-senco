<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class PaymentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'string',
                'exists:students,student_id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1', 
                function ($attribute, $value, $fail) {
                    $studentId = $this->input('student_id');
                    
                    if (!$studentId) return;

                    // 1. Calculate how much the student has already paid
                    $alreadyPaid = DB::table('payments')
                        ->where('student_id', $studentId)
                        ->sum('amount');

                    $limit = 4000;
                    $remaining = $limit - $alreadyPaid;

                    // 2. Check if this new payment pushes them over the 4k limit
                    if (($alreadyPaid + $value) > $limit) {
                        $message = $remaining <= 0 
                            ? "This student has already reached the ₱4,000 limit."
                            : "Amount exceeds the remaining balance. The student can only pay up to ₱" . number_format($remaining, 2) . " more.";
                        
                        $fail($message);
                    }
                },
            ],
            'full_name' => 'nullable|string',
        ];
    }

    /**
     * Optional: Custom error messages for clarity
     */
    public function messages(): array
    {
        return [
            'student_id.exists' => 'The provided Student ID does not exist in our records.',
            'amount.min' => 'The minimum payment allowed is ₱1.00.',
        ];
    }
}