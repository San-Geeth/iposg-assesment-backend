<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Services\Helpers\PaymentServiceHelper;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected ExchangeRateService $exchangeRateService;
    protected PaymentRepository  $paymentRepository;
    protected PaymentServiceHelper  $paymentServiceHelper;

    public function __construct(PaymentRepository $paymentRepository, PaymentServiceHelper $paymentServiceHelper,
                                ExchangeRateService $exchangeRateService,)
    {
        $this->paymentServiceHelper = $paymentServiceHelper;
        $this->paymentRepository = $paymentRepository;
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Desc: Populating payment records after read
     * uploaded CSV with validating
     *
     * @param string $csvData
     * @param string $fileId
     * @return void
     * @throws Exception
     */
    public function populatePayments(string $csvData, string $fileId): array
    {
        $summary = [
            'total_rows' => 0,
            'processed' => 0,
            'skipped' => 0,
            'errors' => [],
            'start_time' => microtime(true),
        ];

        try {
            $this->exchangeRateService->updateAndGetRates();

            $this->paymentServiceHelper->processCsvInChunks($csvData, $fileId, $summary);

            $summary['execution_time'] = round(microtime(true) - $summary['start_time'], 2);
            $this->paymentServiceHelper->logProcessingSummary($summary, $fileId);

            return $summary;

        } catch (Exception $exception) {
            Log::error('An error occurred while populating csv file (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

    /**
     * Desc: Saving payment record to database after
     * populating function validates data
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function savePayment(array $data)
    {
        try {
            return $this->paymentRepository->saveNewPayment($data);
        } catch (Exception $exception) {
            Log::error('An error occurred while saving payment (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

    /**
     * Desc: Getting paginated payments records
     *
     * @param int $perPage
     * @return mixed
     * @throws Exception
     */
    public function getPaginatedPayments(int $perPage = 15)
    {
        try {
            return $this->paymentRepository->getPaginatedPayments($perPage);
        } catch (Exception $exception) {
            Log::error('An error occurred while getting paginated payments (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

}
