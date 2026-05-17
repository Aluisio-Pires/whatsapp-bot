<?php

declare(strict_types=1);

namespace App\Data;

final readonly class IncomingWhatsAppMessageData
{
    public function __construct(
        public string $externalId,
        public string $phone,
        public ?string $senderName,
        public string $message,
        public bool $fromMe,
        public array $metadata,
    ) {}

    public static function fromArray(array $payload): ?self
    {
        $data = $payload['data'] ?? [];
        $key = $data['key'] ?? [];
        $messageObj = $data['message'] ?? [];

        $externalId = $key['id'] ?? null;
        $remoteJid = $key['remoteJid'] ?? '';
        $fromMe = (bool) ($key['fromMe'] ?? false);
        $senderName = $data['pushName'] ?? null;

        // Supported message sources
        $message = $messageObj['conversation']
            ?? $messageObj['extendedTextMessage']['text']
            ?? null;

        if (! $externalId || ! $remoteJid || $message === null) {
            return null;
        }

        return new self(
            externalId: (string) $externalId,
            phone: self::normalizePhone($remoteJid),
            senderName: $senderName,
            message: (string) $message,
            fromMe: $fromMe,
            metadata: $payload,
        );
    }

    private static function normalizePhone(string $remoteJid): string
    {
        $phone = str_replace('@s.whatsapp.net', '', $remoteJid);

        return preg_replace('/\D/', '', $phone) ?? '';
    }
}
