<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function savePaymentRecord(SavePaymentRequest  $request)
    {
        $payment = $this->paymentService->createPayment($request->validated());

        return response()->json([
            'message' => 'Payment created successfully',
            'data' => $payment
        ]);
    }
}
