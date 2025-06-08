<?php

namespace App\Repositories;

use App\Models\Payment;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PaymentRepository
{
    /**
     * Desc: Saving payment records
     *
     * @throws Exception
     */
    public function saveNewPayment(array $data)
    {
        try {
            return Payment::create($data);
        } catch (Exception $e) {
            Log::error('An error occurred while saving a payment to the database (repository): ' .
                $e->getMessage() . ' (Line: ' . $e->getLine() . ')');
            throw $e;
        }
    }

    /**
     * Desc: Returning payment record data to
     * send invoices
     *
     * @throws Exception
     */
    public function getUnprocessedPaymentsByDateGroupedByEmail(string $date): Collection
    {
        try {
            return Payment::whereDate('created_at', $date)
                ->where('processed', false)
                ->get()
                ->groupBy('customer_email');
        } catch (Exception $exception) {
            Log::error('An error occurred while getting a ude payments from the database (repository): ' .
                $exception->getMessage() . ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

    /**
     * Desc: Updating payment record as processed
     *
     * @throws Exception
     */
    public function markPaymentsAsProcessed(Collection $payments): void
    {
        try {
            foreach ($payments as $payment) {
                $payment->update([
                    'processed' => true,
                    'processed_at' => now(),
                ]);
            }
        } catch (Exception $exception) {
            Log::error('An error occurred while updating payment as processed (repository): ' .
                $exception->getMessage() . ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

    /**
     * Desc: Update invoice_id for the given payment records
     *
     * @param array $paymentIds
     * @param int $invoiceId
     * @throws Exception
     */
    public function updateInvoiceIdForPayments(array $paymentIds, int $invoiceId): void
    {
        try {
            Payment::whereIn('id', $paymentIds)->update([
                'invoice_id' => $invoiceId
            ]);
        } catch (Exception $exception) {
            Log::error('An error occurred while updating invoice_id on payments (repository): ' .
                $exception->getMessage() . ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

}
