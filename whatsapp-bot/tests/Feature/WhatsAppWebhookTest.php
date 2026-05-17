<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('valid webhook is accepted and dispatches job', function () {
    Queue::fake();

    $payload = [
        'data' => [
            'key' => [
                'remoteJid' => '5586999999999@s.whatsapp.net',
                'id' => 'ABC123XYZ',
                'fromMe' => false,
            ],
            'message' => [
                'conversation' => 'Hello bot!',
            ],
            'pushName' => 'John Doe',
        ],
    ];

    $response = $this->postJson('/api/webhooks/whatsapp', $payload);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Message received and processing started.']);

    $this->assertDatabaseHas('contacts', [
        'phone' => '5586999999999',
        'name' => 'John Doe',
    ]);

    $this->assertDatabaseHas('messages', [
        'external_id' => 'ABC123XYZ',
        'content' => 'Hello bot!',
        'role' => 'user',
    ]);

    Queue::assertPushed(ProcessIncomingMessageJob::class);
});

test('duplicate messages are ignored', function () {
    Queue::fake();

    $externalId = 'DUPLICATE_ID';
    Message::factory()->create(['external_id' => $externalId]);

    $payload = [
        'data' => [
            'key' => [
                'remoteJid' => '5586999999999@s.whatsapp.net',
                'id' => $externalId,
                'fromMe' => false,
            ],
            'message' => [
                'conversation' => 'Repeat message',
            ],
        ],
    ];

    $response = $this->postJson('/api/webhooks/whatsapp', $payload);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Duplicate message ignored.']);

    Queue::assertNotPushed(ProcessIncomingMessageJob::class);
});

test('self messages are ignored', function () {
    Queue::fake();

    $payload = [
        'data' => [
            'key' => [
                'remoteJid' => '5586999999999@s.whatsapp.net',
                'id' => 'SELF_ID',
                'fromMe' => true,
            ],
            'message' => [
                'conversation' => 'Message from me',
            ],
        ],
    ];

    $response = $this->postJson('/api/webhooks/whatsapp', $payload);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Self message ignored.']);

    Queue::assertNotPushed(ProcessIncomingMessageJob::class);
});

test('invalid or empty messages are ignored', function () {
    Queue::fake();

    $payload = [
        'data' => [
            'key' => [
                'remoteJid' => '5586999999999@s.whatsapp.net',
                'id' => 'EMPTY_ID',
                'fromMe' => false,
            ],
            'message' => [
                // No conversation field
            ],
        ],
    ];

    $response = $this->postJson('/api/webhooks/whatsapp', $payload);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Invalid payload or unsupported message type.']);

    Queue::assertNotPushed(ProcessIncomingMessageJob::class);
});
