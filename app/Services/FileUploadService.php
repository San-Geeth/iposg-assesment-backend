<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{

    /**
     * Desc: Function to upload file to the AWS s3 bucket
     *
     * @param string $localPath
     * @return array
     * @throws Exception
     */
    public function uploadCsvToS3(string $localPath): array
    {
        try {
            $uuid = (string) Str::uuid();
            $filePath = 'payments/' . $uuid . '.csv';

            $csvData = file_get_contents($localPath);

            Storage::disk('s3')->put($filePath, $csvData, 'public');

            return [$uuid, $filePath, $csvData];
        } catch (Exception $exception) {
            Log::error("File upload to S3 failed", [
                'path' => $localPath,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }
}
