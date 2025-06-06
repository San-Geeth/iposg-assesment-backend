<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentFileRequest;
use App\Models\File;
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

        $uuid = Str::uuid();
        $filePath = 'payments/' . $uuid . '.csv';

        try {
            $csvData = file_get_contents($file->getRealPath());

            Storage::disk('s3')->put($filePath, $csvData, 'public');

            // Save to database
            File::create([
                'id' => $uuid,
                'path' => $filePath,
            ]);

            $this->paymentsPopulateService->process($csvData, $uuid);

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
