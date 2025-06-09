<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    protected InvoiceRepository $invoiceRepository;
    protected PaymentRepository $paymentRepository;

    public function __construct(InvoiceRepository $invoiceRepository, PaymentRepository $paymentRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Desc: Function to generate invoice after sending email
     * and updating invoice id in payments table
     *
     * @param array $payments
     * @return void
     * @throws Exception
     */
    public function generateInvoiceAfterEmail(array $payments): void
    {
        try {
            $paymentIds = collect($payments)->pluck('id')->toArray();

            $invoice = $this->invoiceRepository->createInvoice($paymentIds);

            $this->paymentRepository->updateInvoiceIdForPayments($paymentIds, $invoice->id);
        } catch (Exception $exception) {
            Log::error('An error occurred while updating invoice id (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }
}
