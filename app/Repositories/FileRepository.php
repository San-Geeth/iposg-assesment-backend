<?php

namespace App\Repositories;

use App\Models\File;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileRepository
{
    /**
     * Desc: Save file data to the file table for later use
     *
     * @param string $path
     * @param UploadedFile $file
     * @param string|null $uuid
     * @return File
     * @throws Exception
     */
    public function saveFileData(string $path, UploadedFile $file, string $uuid = null): File
    {
        try {
            return File::create([
                'id' => $uuid ?? Str::uuid()->toString(),
                'path' => $path,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        } catch (Exception $exception) {
            Log::error('An error occurred while saving a file data to the database (repository): ' .
                $exception->getMessage() . ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }
}
