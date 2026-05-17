<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public function generateResponse(Conversation $conversation): string
    {
        $messages = $this->buildContext($conversation);

        $response = Http::withToken((string) config('services.openai.api_key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => $messages,
            ])
            ->throw()
            ->json();

        return (string) ($response['choices'][0]['message']['content'] ?? '');
    }

    private function buildContext(Conversation $conversation): array
    {
        $context = [
            [
                'role' => 'system',
                'content' => config('services.openai.system_prompt'),
            ],
        ];

        $history = $conversation->messages()
            ->latest()
            ->take(20)
            ->get()
            ->reverse();

        foreach ($history as $message) {
            $context[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        return $context;
    }
}
