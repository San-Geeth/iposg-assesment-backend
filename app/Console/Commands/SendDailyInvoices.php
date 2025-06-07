<?php

namespace App\Console\Commands;

use App\Mail\InvoiceEmail;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->toDateString();

        // Step 1: Find unprocessed payments for today
        $payments = Payment::whereDate('created_at', $today)
            ->where('processed', false)
            ->get()
            ->groupBy('customer_email');

        foreach ($payments as $email => $customerPayments) {
            // Step 2: Generate HTML invoice
            $invoiceHtml = view('emails.invoice', [
                'payments' => $customerPayments,
                'customer_email' => $email,
            ])->render();

            // Step 3: Send the email
            Mail::to($email)->send(new InvoiceEmail($invoiceHtml));

            // Step 4: Mark as processed
            foreach ($customerPayments as $payment) {
                $payment->update([
                    'processed' => true,
                    'processed_at' => now(),
                ]);
            }
        }

        $this->info("Invoices sent to " . $payments->keys()->count() . " customers.");
    }
}
