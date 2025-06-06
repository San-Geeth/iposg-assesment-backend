<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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

    public function process(string $csvData): void
    {

        $this->exchangeRateService->updateAndGetRates();

        $csvData = str_replace(["\r\n", "\r"], "\n", $csvData);

        $lines = array_filter(array_map('trim', explode("\n", $csvData)));

        if (empty($lines)) {
            Log::warning('CSV content appears empty or improperly formatted.');
            return;
        }

        $rows = array_map('str_getcsv', $lines);

        $header = array_map('trim', array_shift($rows));

        Log::info('Parsed rows:', $rows);

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

                $this->paymentService->createPayment($validated);
                Log::info('Payment saved', ['reference' => $validated['reference_no']]);
            } catch (\Throwable $e) {
                Log::error('Failed to process payment row', [
                    'error' => $e->getMessage(),
                    'row' => $data
                ]);
            }
        }
    }

    protected function validateRow(array $data): array
    {
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
    }

    protected function convertToUSD(string $currency, float $amount): float
    {
        if ($currency === 'USD') {
            return $amount;
        }

        $response = Http::get("https://api.exchangerate.host/latest?base=$currency&symbols=USD");

        if (!$response->ok()) {
            throw new \Exception("Failed to fetch exchange rate for $currency");
        }

        $rate = $response->json()['rates']['USD'] ?? null;

        if (!$rate) {
            throw new \Exception("Exchange rate for $currency to USD not found");
        }

        return round($amount * $rate, 2);
    }

}
