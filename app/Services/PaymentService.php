<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected ExchangeRateService $exchangeRateService;
    protected PaymentRepository  $paymentRepository;
    protected SlackNotificationService $slackNotificationService;
    protected int $jobSuccessAlertCount;
    protected int $csvChunksBatchSize;

    public function __construct(PaymentRepository $paymentRepository, ExchangeRateService $exchangeRateService,
                                SlackNotificationService $slackNotificationService)
    {
        $this->paymentRepository = $paymentRepository;
        $this->exchangeRateService = $exchangeRateService;
        $this->slackNotificationService = $slackNotificationService;

        $this->jobSuccessAlertCount = config('iposg.notification.slack.alert_max_count');
        $this->csvChunksBatchSize = config('iposg.payments.csv_process.batch.size');
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

            $this->processCsvInChunks($csvData, $fileId, $summary);

            $summary['execution_time'] = round(microtime(true) - $summary['start_time'], 2);
            $this->logProcessingSummary($summary, $fileId);

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

    /**
     * Desc: process CSV data in chunks to manage memory usage
     *
     * @param string $csvData
     * @param string $fileId
     * @param array $summary
     * @throws Exception
     */
    public function processCsvInChunks(string $csvData, string $fileId, array &$summary): void
    {
        try {
            $csvData = str_replace(["\r\n", "\r"], "\n", $csvData);
            $record  = array_filter(array_map('trim', explode("\n", $csvData)));

            if (empty($record )) {
                Log::warning('CSV content appears empty or improperly formatted.');
                return;
            }

            $header = array_map('trim', str_getcsv(array_shift($record )));
            $summary['total_rows'] = count($record );

            $batch = [];
            $batchSize = $this->csvChunksBatchSize;
            $currentBatchNumber = 1;

            foreach ($record  as $line) {
                try {
                    $row = str_getcsv($line);
                    if (count($row) < count($header)) {
                        Log::warning('Skipping row: missing columns', ['row' => $row]);
                        continue;
                    }

                    $data = array_combine($header, $row);
                    $data = $this->cleanRow($data);
                    $validated = $this->validateRow($data);

                    if ($validated !== null) {
                        $usdRate = $this->exchangeRateService->getRate($validated['currency']);
                        $validated['usd_amount'] = $usdRate * $validated['amount'];
                        $validated['exchange_rate'] = $usdRate;
                        $validated['file_id'] = $fileId;

                        $batch[] = $validated;

                        if (count($batch) >= $batchSize) {
                            $this->processBatch($batch, $currentBatchNumber);
                            $summary['processed'] += count($batch);
                            $currentBatchNumber++;
                            $batch = [];
                        }
                    }
                } catch (Exception $exception) {
                    $summary['skipped']++;
                    $summary['errors'][] = [
                        'message' => $exception->getMessage(),
                        'row' => $row ?? null
                    ];
                }
            }

            if (!empty($batch)) {
                $this->processBatch($batch, $currentBatchNumber);
                $summary['processed'] += count($batch);
            }
        } catch (Exception $exception) {
            Log::error('An error occurred while processing csv chunks (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

    /**
     * Desc: process and log a batch of validated payments
     *
     * @param array $batch
     * @param int $batchNumber
     * @throws Exception
     */
    protected function processBatch(array $batch, int $batchNumber): void
    {
        try {
            $batchReferences = array_column($batch, 'reference_no');

            // Save all payments in the batch
            foreach ($batch as $payment) {
                $this->savePayment($payment);
            }

            // Log the entire batch as JSON
            Log::info("Batch {$batchNumber} processed", [
                'batch_size' => count($batch),
                'references' => $batchReferences,
                'sample_data' => array_slice($batch, 0, 3)
            ]);

        } catch (Exception $exception) {
            Log::error("Failed to process batch {$batchNumber}", [
                'error' => $exception->getMessage(),
                'batch_size' => count($batch),
                'batch_number' => $batchNumber
            ]);
            throw $exception;
        }
    }

    /**
     * Desc: log summary of processing
     *
     * @param array $summary
     * @param string $fileId
     * @throws Exception
     */
    public function logProcessingSummary(array $summary, string $fileId): void
    {
        try {
            $message = sprintf(
                "Payment processing completed. File ID: %s, Total: %d, Processed: %d, Skipped: %d, Time: %ss",
                $fileId,
                $summary['total_rows'],
                $summary['processed'],
                $summary['skipped'],
                $summary['execution_time']
            );

            Log::info($message);

            if ($summary['processed'] > $this->jobSuccessAlertCount) {
                $this->slackNotificationService->sendPaymentSavingsJobSuccessMessage("✅ " . $message);
            }

            if (!empty($summary['errors'])) {
                Log::warning('Processing errors summary', [
                    'total_errors' => count($summary['errors']),
                    'sample_errors' => array_slice($summary['errors'], 0, 5)
                ]);
            }
        } catch (Exception $exception) {
            Log::error('An error occurred while logging populating summaries (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }
    /**
     * Desc: Validate data in rows before
     * populate to the database
     *
     * @param array $data
     * @return array|null
     */
    protected function validateRow(array $data): ?array
    {
        try {
            $required = ['customer_email', 'reference_no', 'date_time', 'currency', 'amount'];

            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Missing required field: $field");
                }
            }

            if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
                // Try to fix common typo: e.g. double dots, missing @, etc. (example: 'test..org' => 'test.org')
                $fixed = preg_replace('/\.{2,}/', '.', $data['customer_email']);
                if (filter_var($fixed, FILTER_VALIDATE_EMAIL)) {
                    $data['customer_email'] = $fixed;
                } else {
                    throw new \Exception("Invalid email format: " . $data['customer_email']);
                }
            }

            // Validate amount is numeric and > 0
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                throw new \Exception("Invalid amount: " . $data['amount']);
            }

            // Validate currency is in a known list (optional, could have a config array)
            $validCurrencies = ['USD','EUR','AUD','CAD','CHF','INR','JPY','CNY','LKR','SGD','GBP','HKD'];
            if (!in_array($data['currency'], $validCurrencies)) {
                throw new \Exception("Unknown currency: " . $data['currency']);
            }

            // Validate date (already normalized)
            if (empty($data['date_time']) || strtotime($data['date_time']) === false) {
                throw new \Exception("Invalid date format: " . $data['date_time']);
            }

            return [
                'customer_id'   => $data['customer_id'] ?? null,
                'customer_email'=> $data['customer_email'],
                'reference_no'  => $data['reference_no'],
                'payment_date'  => date('Y-m-d', strtotime($data['date_time'])),
                'currency'      => $data['currency'],
                'amount'        => (float)$data['amount']
            ];
        } catch (Exception $exception) {
            Log::error('An error occurred while validating payment row (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            return null;
        }
    }

    /**
     * Desc: Attempt to clean/fix anomaly data in a row.
     *
     * @param array $data
     * @return array
     */
    protected function cleanRow(array $data): array
    {
        try {
            // Remove leading/trailing whitespace from all fields
            foreach ($data as $key => $value) {
                $data[$key] = trim($value ?? '');
            }

            // Attempt to fix emails with leading/trailing whitespace or common typos
            if (!empty($data['customer_email'])) {
                $data['customer_email'] = strtolower($data['customer_email']);
                $data['customer_email'] = str_replace([' ', ';'], ['', ''], $data['customer_email']);
                // Remove leading whitespace, e.g. ' leadingwhitespace@email.com'
                $data['customer_email'] = ltrim($data['customer_email']);
            }

            // Attempt to fix amount: remove commas, handle dots, convert to float
            if (isset($data['amount'])) {
                $amount = str_replace([',', ' '], ['', ''], $data['amount']);
                $data['amount'] = is_numeric($amount) ? (float)$amount : null;
            }

            // Attempt to fix currency: uppercase, remove whitespace
            if (isset($data['currency'])) {
                $data['currency'] = strtoupper(trim($data['currency']));
            }

            // Normalize date_time, try to parse
            if (!empty($data['date_time'])) {
                // Acceptable date formats: 'Y-m-d H:i:s', 'Y/m/d H:i:s', etc.
                $parsed = false;
                $formats = ['Y-m-d H:i:s', 'Y/m/d H:i:s', 'd-m-Y H:i:s', 'd/m/Y H:i:s', 'Y-m-d', 'd-m-Y'];
                foreach ($formats as $format) {
                    $dt = \DateTime::createFromFormat($format, $data['date_time']);
                    if ($dt !== false) {
                        $data['date_time'] = $dt->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    }
                }
                // If still not parsed, try strtotime fallback
                if (!$parsed && strtotime($data['date_time'])) {
                    $data['date_time'] = date('Y-m-d H:i:s', strtotime($data['date_time']));
                }
            }

            // customer_id: uppercase
            if (!empty($data['customer_id'])) {
                $data['customer_id'] = strtoupper($data['customer_id']);
            }

            return $data;
        } catch (Exception $exception) {
            Log::error('An error occurred while cleaning row (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            return [];
        }
    }
}
