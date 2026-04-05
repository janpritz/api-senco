<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Payment; // Import your Payment model
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentUpdateReceiptMail;

class SendPaymentUpdateReceiptJob implements ShouldQueue
{
    use Queueable;
    protected $payment;

    // Retry 3 times if the mail server fails
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // We assume your Student model has an 'email' field
        $recipient = $this->payment->student->email;

        if ($recipient) {
            Mail::to($recipient)->send(new PaymentUpdateReceiptMail($this->payment));
        }
    }
}
