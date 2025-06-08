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
     * Desc: Saving payment record to database after
     * populating function validates data
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function savePayment(array $data)
    {
        try {
            return $this->paymentRepository->saveNewPayment($data);
        } catch (Exception $exception) {
            Log::error('An error occurred while saving payment (controller): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }
}
