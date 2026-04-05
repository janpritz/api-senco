<?php
namespace App\Jobs;

use App\Models\Payment;
use App\Mail\PaymentReceiptMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;

    // Retry 3 times if the mail server fails
    public $tries = 3;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function handle(): void
    {
        // We assume your Student model has an 'email' field
        $recipient = $this->payment->student->email;

        if ($recipient) {
            Mail::to($recipient)->send(new PaymentReceiptMail($this->payment));
        }
    }
}