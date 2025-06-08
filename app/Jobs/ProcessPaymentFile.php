<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentFile implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public string $csvData;
    public string $fileId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $csvData, string $fileId)
    {
        $this->csvData = $csvData;
        $this->fileId = $fileId;
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentsPopulateService): void
    {
        $paymentsPopulateService->populatePayments($this->csvData, $this->fileId);
    }
}
