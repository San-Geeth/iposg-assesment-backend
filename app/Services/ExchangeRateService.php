<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    protected string $apiUrl;

    public function __construct()
    {
        $baseUrl = env('API_EXCHANGE_BASE_URL');
        $apiKey = env('API_EXCHANGE_KEY');
        $baseCurrency = env('API_EXCHANGE_DEFAULT');

        $this->apiUrl = "{$baseUrl}/{$apiKey}/latest/{$baseCurrency}";
    }

    /**
     * @throws Exception
     */
    public function updateAndGetRates(): ?array
    {
        try {
            $response = Http::get($this->apiUrl);

            if ($response->json('result') === 'success') {
                $data = $response->json();

                // Cache for 24 hours
                Cache::put('exchange_rates', $data, now()->addHours(24));
                Log::info('Exchange rate API response:', ['data' => $data]);
                return $data;
            }

            // Fallback to cached data
            return Cache::get('exchange_rates');
        } catch (Exception $e) {
            Log::error('An error occurred getting exchange rates: ' . $e->getMessage() .
                ' (Line: ' . $e->getLine() . ')');
            throw $e;
        }
    }

    public function getRate(string $currency): ?float
    {
        $rates = Cache::get('exchange_rates');

        return $rates['conversion_rates'][$currency] ?? null;
    }
}
