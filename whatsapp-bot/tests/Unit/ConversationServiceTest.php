<?php

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ConversationService;
});

test('it can find or create a contact', function () {
    $phone = '5586999999999';
    $name = 'John Doe';

    $contact = $this->service->findOrCreateContact($phone, $name);

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->phone)->toBe($phone)
        ->and($contact->name)->toBe($name);

    $contact2 = $this->service->findOrCreateContact($phone, 'New Name');

    expect($contact2->id)->toBe($contact->id)
        ->and($contact2->name)->toBe('New Name');
});

test('it can find or create a conversation', function () {
    $contact = Contact::factory()->create();

    $conversation = $this->service->findOrCreateConversation($contact);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->contact_id)->toBe($contact->id);

    $conversation2 = $this->service->findOrCreateConversation($contact);

    expect($conversation2->id)->toBe($conversation->id);
});

test('it can store a user message', function () {
    $conversation = Conversation::factory()->create();
    $externalId = 'msg_123';
    $content = 'Hello world';
    $metadata = ['foo' => 'bar'];

    $message = $this->service->storeUserMessage($conversation, $externalId, $content, $metadata);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->conversation_id)->toBe($conversation->id)
        ->and($message->external_id)->toBe($externalId)
        ->and($message->role)->toBe('user')
        ->and($message->content)->toBe($content)
        ->and($message->metadata)->toBe($metadata);
});

test('it can store an assistant message', function () {
    $conversation = Conversation::factory()->create();
    $content = 'How can I help you?';

    $message = $this->service->storeAssistantMessage($conversation, $content);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->conversation_id)->toBe($conversation->id)
        ->and($message->role)->toBe('assistant')
        ->and($message->content)->toBe($content);
});
