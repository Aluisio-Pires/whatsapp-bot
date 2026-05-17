<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\IncomingWhatsAppMessageData;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Message;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request, ConversationService $conversationService): JsonResponse
    {
        $payload = $request->all();

        $data = IncomingWhatsAppMessageData::fromArray($payload);

        if (! $data) {
            return response()->json(['message' => 'Invalid payload or unsupported message type.'], 200);
        }

        if ($data->fromMe) {
            return response()->json(['message' => 'Self message ignored.'], 200);
        }

        // Ignore duplicate messages using external_id
        if (Message::where('external_id', $data->externalId)->exists()) {
            return response()->json(['message' => 'Duplicate message ignored.'], 200);
        }

        $contact = $conversationService->findOrCreateContact($data->phone, $data->senderName);
        $conversation = $conversationService->findOrCreateConversation($contact);

        $message = $conversationService->storeUserMessage(
            $conversation,
            $data->externalId,
            $data->message,
            $data->metadata
        );

        ProcessIncomingMessageJob::dispatch($conversation);

        return response()->json(['message' => 'Message received and processing started.'], 200);
    }
}
