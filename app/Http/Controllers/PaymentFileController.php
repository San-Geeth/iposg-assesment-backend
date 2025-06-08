<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentFileRequest;
use App\Jobs\ProcessPaymentFile;
use App\Models\File;
use App\Services\FileUploadService;
use App\Services\PaymentsPopulateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentFileController extends Controller
{
    protected PaymentsPopulateService $paymentsPopulateService;
    protected FileUploadService $fileUploadService;

    public function __construct(PaymentsPopulateService $paymentsPopulateService, FileUploadService $fileUploadService)
    {
        $this->paymentsPopulateService = $paymentsPopulateService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Desc: This method uploads the file to S3, saves metadata to the database,
     * and dispatches a job to process the file asynchronously.
     *
     * @param UploadPaymentFileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFile(UploadPaymentFileRequest $request): JsonResponse
    {
        $file = $request->file('file');

        try {
            [$uuid, $filePath, $csvData] = $this->fileUploadService->uploadCsvToS3($file->getRealPath());

            File::create([
                'id' => $uuid,
                'path' => $filePath,
            ]);

            ProcessPaymentFile::dispatch($csvData, $uuid);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and processing has started.',
                'reference_id' => $uuid,
            ], 202);
        } catch (Exception $exception) {
            Log::error('An error occurred while uploading and processing file (controller): ' .
                $exception->getMessage() . ' (Line: ' . $exception->getLine() . ')');

            return response()->json([
                'message' => 'Error processing file.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
