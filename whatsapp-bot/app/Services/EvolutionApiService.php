<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EvolutionApiService
{
    public function sendMessage(string $phone, string $message): void
    {
        $url = sprintf(
            '%s/message/sendText/%s',
            config('services.evolution.url'),
            config('services.evolution.instance')
        );

        Http::withHeaders([
            'apikey' => config('services.evolution.api_key'),
        ])
            ->post($url, [
                'number' => $phone,
                'text' => $message,
            ])
            ->throw();
    }
}
