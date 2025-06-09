<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePaymentRequest;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;
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

    /**
     * Desc: Getting paginated payments list using payment service
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function getPaginatedPaymentRecords(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $payments = $this->paymentService->getPaginatedPayments($perPage);

            return response()->json($payments);
        } catch (Exception $exception) {
            Log::error('An error occurred while getting paginated payment records (controller): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }
}
