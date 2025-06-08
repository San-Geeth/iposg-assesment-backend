<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotificationService
{
    protected string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('notification.outgoing_webhook_url');
    }

    /**
     * Desc: Sending message to slack channel through the
     * integration
     *
     * @param string $message
     * @return void
     */
    public function sendPaymentSavingsJobSuccessMessage(string $message): void
    {
        try {
            $response = Http::post($this->webhookUrl, [
                'text' => $message
            ]);

            if (!$response->successful()) {
                Log::error('Slack notification failed', [
                    'message' => $message,
                    'response' => $response->body(),
                    'status' => $response->status(),
                ]);
            }
        } catch (Exception $exception) {
            Log::error('Slack notification exception', [
                'message' => $message,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
