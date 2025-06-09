<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\Exception;

class InvoiceRepository
{
    /**
     * Desc: Saving invoice data to database after send
     * invoice emails
     *
     * @param array $paymentIds
     * @return Invoice
     */
    public function createInvoice(array $paymentIds): Invoice
    {
        try {
            return Invoice::create([
                'id' => Str::uuid(),
                'payment_ids' => $paymentIds,
            ]);
        } catch (Exception $exception) {
            Log::error('An error occurred while invoice data to the database (repository): ' .
                $exception->getMessage() . ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }
}
