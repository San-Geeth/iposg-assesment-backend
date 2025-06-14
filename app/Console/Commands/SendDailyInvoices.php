<?php

namespace App\Console\Commands;

use App\Mail\InvoiceEmail;
use App\Repositories\PaymentRepository;
use App\Services\InvoiceService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyInvoices extends Command
{

    protected PaymentRepository $paymentRepository;
    protected InvoiceService $invoiceService;

    public function __construct(PaymentRepository $paymentRepository, InvoiceService $invoiceService)
    {
        parent::__construct();
        $this->paymentRepository = $paymentRepository;
        $this->invoiceService = $invoiceService;
    }


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
     * Desc: Command for send invoice emails daily to customers
     * where payments unprocessed
     *
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info("cron running");
            $today = now()->toDateString();

            $paymentsGrouped = $this->paymentRepository
                ->getUnprocessedPaymentsByDateGroupedByEmail($today);

            foreach ($paymentsGrouped as $email => $customerPayments) {
                $invoiceHtml = view('emails.invoice', [
                    'payments' => $customerPayments,
                    'customer_email' => $email,
                ])->render();

                $this->invoiceService->generateInvoiceAfterEmail($customerPayments->all());

                Mail::to($email)->send(new InvoiceEmail($invoiceHtml));

                $this->paymentRepository->markPaymentsAsProcessed($customerPayments);

            }

            $this->info("Invoices sent to " . $paymentsGrouped->keys()->count() . " customers.");
        }  catch (Exception $exception) {
            Log::error('An error occurred while sending daily invoices (command): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
        }
    }
}
