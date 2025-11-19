<?php

namespace Obsidiane\AuthBundle\Exception;

/**
 * Exception structurée pour refléter une erreur HTTP de l'API Auth.
 */
final class ApiErrorException extends \RuntimeException
{
    /**
     * @param array<string,mixed>|null $details
     * @param array<string,mixed>      $payload
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $errorCode,
        private readonly ?array $details = null,
        private readonly array $payload = [],
        string $message = '',
    ) {
        parent::__construct($message === '' ? $errorCode : $message, $statusCode);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromPayload(int $statusCode, array $payload = []): self
    {
        $errorCode = isset($payload['error']) ? (string) $payload['error'] : 'unknown_error';
        $details = isset($payload['details']) && is_array($payload['details']) ? $payload['details'] : null;
        $message = sprintf('%s [%d]', $errorCode, $statusCode);

        return new self($statusCode, $errorCode, $details, $payload, $message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * @return array<string,mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}

