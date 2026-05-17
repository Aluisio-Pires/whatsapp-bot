<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it can generate a response from openai', function () {
    config(['services.openai.api_key' => 'test_key']);
    config(['services.openai.model' => 'gpt-4o-mini']);
    config(['services.openai.system_prompt' => 'Be helpful.']);

    $conversation = Conversation::factory()->create();
    Message::factory()->for($conversation)->user()->create(['content' => 'Hello']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Hi there! How can I help?']],
            ],
        ], 200),
    ]);

    $service = new OpenAIService;
    $response = $service->generateResponse($conversation);

    expect($response)->toBe('Hi there! How can I help?');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.openai.com/v1/chat/completions' &&
               $request['model'] === 'gpt-4o-mini' &&
               count($request['messages']) === 2 && // system + 1 message
               $request['messages'][0]['role'] === 'system' &&
               $request['messages'][1]['role'] === 'user';
    });
});
