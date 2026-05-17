<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationService;
use App\Services\EvolutionApiService;
use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('job processes message, generates response and sends via evolution', function () {
    $conversation = Conversation::factory()->create();
    $message = Message::factory()->for($conversation)->user()->create(['content' => 'Hello']);

    $mockOpenAI = mock(OpenAIService::class);
    $mockOpenAI->shouldReceive('generateResponse')
        ->once()
        ->with($conversation)
        ->andReturn('Bot response');

    $this->app->instance(OpenAIService::class, $mockOpenAI);

    $mockEvolution = mock(EvolutionApiService::class);
    $mockEvolution->shouldReceive('sendMessage')
        ->once()
        ->with($conversation->contact->phone, 'Bot response');

    $this->app->instance(EvolutionApiService::class, $mockEvolution);

    $job = new ProcessIncomingMessageJob($conversation);
    $job->handle(
        $mockOpenAI,
        app(ConversationService::class),
        $mockEvolution
    );

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'Bot response',
    ]);
});
