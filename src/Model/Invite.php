<?php

namespace Obsidiane\AuthBundle\Model;

use DateTimeImmutable;

final class Invite
{
    public function __construct(
        public int $id,
        public string $email,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $expiresAt,
        public ?DateTimeImmutable $acceptedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $createdAt = new DateTimeImmutable((string) ($data['createdAt'] ?? 'now'));
        $expiresAt = new DateTimeImmutable((string) ($data['expiresAt'] ?? 'now'));

        $acceptedAt = null;
        if (isset($data['acceptedAt']) && $data['acceptedAt'] !== null && $data['acceptedAt'] !== '') {
            $acceptedAt = new DateTimeImmutable((string) $data['acceptedAt']);
        }

        return new self(
            isset($data['id']) ? (int) $data['id'] : 0,
            (string) ($data['email'] ?? ''),
            $createdAt,
            $expiresAt,
            $acceptedAt,
        );
    }
}
