<?php

namespace App\Repositories;

use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentRepository
{
    /**
     * @throws Exception
     */
    public function create(array $data)
    {
        try {
            return Payment::create($data);
        } catch (Exception $e) {
            Log::error('An error occurred while saving a payment to the database (repository): ' .
                $e->getMessage() . ' (Line: ' . $e->getLine() . ')');
            throw $e;
        }
    }

}
