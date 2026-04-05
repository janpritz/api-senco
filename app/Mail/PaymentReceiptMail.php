<?php

namespace App\Mail;

use App\Models\Payment; // Import your Payment model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The payment instance.
     * Making this public allows it to be accessed in your markdown file.
     */
    public $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function envelope(): Envelope
    {
        $subject = 'Official Payment Receipt - SENCO 2026';

        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        //Log::info('PaymentReceiptMail initialized with payment ID: ' . $this->payment->id . ' and isUpdate: ' . ($this->isUpdate ? 'true' : 'false'));
        // 1. Calculate the total payments for this specific student
        $totalPaid = Payment::where('student_id', $this->payment->student_id)->sum('amount');
        $goal = 4000;

        // 2. Determine Status
        $isFullyPaid = $totalPaid >= $goal;
        $status = $isFullyPaid ? '✅ Fully Paid' : '⏳ Partial Contribution';

        // Dynamic Intro with Apology
        $introMessage = "We have successfully recorded your payment. Your updated transaction details are provided below.";

        // 3. Prepare the Note Content
        if ($isFullyPaid) {
            $noteContent = '<p style="padding:15px; margin:0;"><strong>Congratulations!</strong> Your graduation dues are now settled in full. You are officially cleared from this financial requirement.</p>';
        } else {
            $remaining = $goal - $totalPaid;
            $noteContent = '<p style="padding:15px; margin:0;"><strong>You still have an outstanding balance of ₱' . number_format($remaining, 2) . '</strong>. Please pay this on the schedule announced by SENCO</p>';
        }

        return new Content(
            view: 'emails.payments.receipt', // Using a standard view instead of markdown for full HTML control
            with: [
                'name'          => $this->payment->student->full_name,
                'id'            => $this->payment->student_id,
                'amount'        => $this->payment->amount,
                'portalCode'    => $this->payment->student->portal_code,
                'receiptId'     => $this->payment->reference_number,
                'formattedDate' => Carbon::parse($this->payment->created_at)
                    ->timezone('Asia/Manila')
                    ->format('F d, Y h:i A'),
                'statusDisplay' => $status,
                'noteContent'   => $noteContent,
                'introMessage'  => $introMessage,

            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        // If you ever want to attach a PDF version of the receipt, 
        // this is where you'd add it.
        return [];
    }
}
