<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentFileRequest;
use App\Jobs\ProcessPaymentFile;
use App\Services\PaymentsPopulateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentFileController extends Controller
{
    protected PaymentsPopulateService $paymentsPopulateService;

    public function __construct(PaymentsPopulateService $paymentsPopulateService)
    {
        $this->paymentsPopulateService = $paymentsPopulateService;
    }

    public function upload(UploadPaymentFileRequest $request)
    {
        $file = $request->file('file');

        try {
            $csvData = file_get_contents($file->getRealPath());

            Storage::disk('s3')->put('payments/' . Str::uuid() . '.csv',
                $csvData, 'public');

            $this->paymentsPopulateService->process($csvData);

            return response()->json([
                'message' => 'File uploaded and processed successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to process uploaded CSV", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error processing file.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
