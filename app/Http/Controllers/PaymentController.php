<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePaymentRequest;
use App\Services\PaymentService;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Desc: Saving payment record to the database
     * if need to save single record without uploading files
     *
     * @param SavePaymentRequest $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function savePaymentRecord(SavePaymentRequest $request)
    {
        try {
            $payment = $this->paymentService->savePayment($request->validated());

            return response()->json([
                'message' => 'Payment created successfully',
                'data' => $payment
            ]);
        } catch (Exception $exception) {
            Log::error('An error occurred while saving payment record (controller): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
        }
    }
}
