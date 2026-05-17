<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\ConversationService;
use App\Services\EvolutionApiService;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public Conversation $conversation
    ) {}

    public function handle(
        OpenAIService $openAIService,
        ConversationService $conversationService,
        EvolutionApiService $evolutionApiService
    ): void {
        $responseContent = $openAIService->generateResponse($this->conversation);

        if (empty($responseContent)) {
            return;
        }

        $conversationService->storeAssistantMessage($this->conversation, $responseContent);

        $evolutionApiService->sendMessage(
            $this->conversation->contact->phone,
            $responseContent
        );
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
