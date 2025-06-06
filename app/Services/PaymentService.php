<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @throws Exception
     */
    public function createPayment(array $data)
    {
        try {
            return $this->paymentRepository->create($data);
        } catch (Exception $e) {
            Log::error('An error occurred while creating a payment (service): ' . $e->getMessage() .
                ' (Line: ' . $e->getLine() . ')');
            throw $e;
        }
    }
}
