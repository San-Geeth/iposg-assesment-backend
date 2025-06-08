<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class PaymentsPopulateService
{
    protected PaymentService $paymentService;
    protected ExchangeRateService $exchangeRateService;
    protected SlackNotificationService $slackNotificationService;

    protected int $jobSuccessAlertCount;

    public function __construct(PaymentService $paymentService, ExchangeRateService $exchangeRateService,
                                SlackNotificationService $slackNotificationService)
    {
        $this->paymentService = $paymentService;
        $this->exchangeRateService = $exchangeRateService;
        $this->slackNotificationService = $slackNotificationService;

        $this->jobSuccessAlertCount = config('iposg.notification.slack.alert_max_count');
    }


    /**
     * Desc: Populating payment records after read
     * uploaded CSV with validating
     *
     * @param string $csvData
     * @param string $fileId
     * @return void
     */
    public function populatePayments(string $csvData, string $fileId): void
    {
        try {
            $this->exchangeRateService->updateAndGetRates();

            $csvData = str_replace(["\r\n", "\r"], "\n", $csvData);

            $record = array_filter(array_map('trim', explode("\n", $csvData)));

            if (empty($record)) {
                Log::warning('CSV content appears empty or improperly formatted.');
                return;
            }

            $rows = array_map('str_getcsv', $record);

            $header = array_map('trim', array_shift($rows));

            $processedCount = 0;
            foreach ($rows as $row) {
                if (count($row) < count($header)) {
                    Log::warning('Skipping row: missing columns', ['row' => $row]);
                    continue;
                }

                $data = array_combine($header, $row);

                $data = $this->cleanRow($data);

                try {
                    $validated = $this->validateRow($data);
                    $usdRate = $this->exchangeRateService->getRate($validated['currency']);

                    $validated['usd_amount'] = $usdRate * $validated['amount'];
                    $validated['processed'] = true;
                    $validated['exchange_rate'] = $usdRate;
                    $validated['file_id'] = $fileId;

                    $this->paymentService->savePayment($validated);
                    $processedCount++;
                    Log::info('Payment saved', ['reference' => $validated['reference_no']]);
                } catch (Exception $exception) {
                    Log::error('Failed to process payment row', [
                        'error' => $exception->getMessage(),
                        'row' => $data
                    ]);
                }
            }

            Log::info('Processed', ['processed' => $processedCount]);
            if ($processedCount > $this->jobSuccessAlertCount) {
                $this->slackNotificationService->sendPaymentSavingsJobSuccessMessage(
                    "✅ Payment processing completed. File ID: {$fileId}, Records inserted: {$processedCount}"
                );
            }
        } catch (Exception $exception) {
            Log::error('An error occurred while populating csv file (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
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
