<?php

use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('it can send a message via evolution api', function () {
    config(['services.evolution.url' => 'http://evolution.test']);
    config(['services.evolution.api_key' => 'api_123']);
    config(['services.evolution.instance' => 'main']);

    Http::fake([
        'evolution.test/*' => Http::response([], 200),
    ]);

    $service = new EvolutionApiService;
    $service->sendMessage('5586999999999', 'Hello from bot');

    Http::assertSent(function ($request) {
        return $request->url() === 'http://evolution.test/message/sendText/main' &&
               $request->header('apikey')[0] === 'api_123' &&
               $request['number'] === '5586999999999' &&
               $request['text'] === 'Hello from bot';
    });
});
