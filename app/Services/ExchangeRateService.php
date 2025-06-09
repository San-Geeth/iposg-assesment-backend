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
        $baseUrl = config('iposg.currency.exchange_rate_api.base_url');
        $apiKey = config('iposg.currency.exchange_rate_api.api_key');
        $baseCurrency = config('iposg.currency.exchange_rate_api.default_currency');

        $this->apiUrl = "{$baseUrl}/{$apiKey}/latest/{$baseCurrency}";
    }


    /**
     * Desc: Getting daily exchange rates from the public API
     * and caching it to later use for calculations
     *
     * @return array|null
     * @throws Exception
     */
    public function updateAndGetRates(): ?array
    {
        try {
            $response = Http::get($this->apiUrl);

            if ($response->json('result') === 'success') {
                $data = $response->json();

                Cache::put('exchange_rates', $data, now()->addHours(24));
                Log::info('Exchange rate API response:', ['data' => $data]);
                return $data;
            }

            return Cache::get('exchange_rates');
        } catch (Exception $exception) {
            Log::error('An error occurred getting exchange rates: ' . $exception->getMessage() .
                ' (Line: ' . $exception->getLine() . ')');
            throw $exception;
        }
    }

    /**
     * Desc: Returning the USD rate for the
     * related currency
     *
     * @param string $currency
     * @return float|null
     */
    public function getRate(string $currency): ?float
    {
        try {
            $rates = Cache::get('exchange_rates');

            return $rates['conversion_rates'][$currency] ?? null;
        } catch (Exception $exception) {
            Log::error("Failed to get exchange rate for {$currency}: " . $exception->getMessage());
            return null;
        }
    }
}
