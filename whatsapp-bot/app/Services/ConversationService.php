<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;

class ConversationService
{
    public function findOrCreateContact(string $phone, ?string $name): Contact
    {
        return Contact::updateOrCreate(
            ['phone' => $phone],
            ['name' => $name]
        );
    }

    public function findOrCreateConversation(Contact $contact): Conversation
    {
        return $contact->conversation()->firstOrCreate([]);
    }

    public function storeUserMessage(Conversation $conversation, string $externalId, string $content, array $metadata): Message
    {
        return $conversation->messages()->create([
            'external_id' => $externalId,
            'role' => 'user',
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    public function storeAssistantMessage(Conversation $conversation, string $content): Message
    {
        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
        ]);
    }
}
