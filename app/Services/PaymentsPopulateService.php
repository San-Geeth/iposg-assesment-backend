<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class PaymentsPopulateService
{
    protected PaymentService $paymentService;
    protected ExchangeRateService $exchangeRateService;

    public function __construct(PaymentService $paymentService, ExchangeRateService $exchangeRateService)
    {
        $this->paymentService = $paymentService;
        $this->exchangeRateService = $exchangeRateService;
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

            foreach ($rows as $row) {
                if (count($row) < count($header)) {
                    Log::warning('Skipping row: missing columns', ['row' => $row]);
                    continue;
                }

                $data = array_combine($header, $row);

                try {
                    $validated = $this->validateRow($data);
                    $usdRate = $this->exchangeRateService->getRate($validated['currency']);

                    $validated['usd_amount'] = $usdRate * $validated['amount'];
                    $validated['processed'] = true;
                    $validated['exchange_rate'] = $usdRate;
                    $validated['file_id'] = $fileId;

                    $this->paymentService->savePayment($validated);
                    Log::info('Payment saved', ['reference' => $validated['reference_no']]);
                } catch (Exception $exception) {
                    Log::error('Failed to process payment row', [
                        'error' => $exception->getMessage(),
                        'row' => $data
                    ]);
                }
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
                throw new \Exception("Invalid email format: " . $data['customer_email']);
            }

            if (!is_numeric($data['amount'])) {
                throw new \Exception("Invalid amount: " . $data['amount']);
            }

            return [
                'customer_id' => $data['customer_id'],
                'customer_email' => $data['customer_email'],
                'reference_no' => $data['reference_no'],
                'payment_date' => date('Y-m-d', strtotime($data['date_time'])),
                'currency' => $data['currency'],
                'amount' => (float)$data['amount']
            ];
        } catch (Exception $exception) {
            Log::error('An error occurred while validating payment row (service): ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            return null;
        }
    }

}
