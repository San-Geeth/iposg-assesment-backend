<?php

namespace App\Jobs;

use App\Services\PaymentsPopulateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessPaymentFile implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentsPopulateService $paymentsPopulateService): void
    {
        Log::info("ProcessPaymentFile");
        $fileContents = Storage::disk('s3')->get($this->filePath);

        $paymentsPopulateService->process($fileContents);
    }
}
