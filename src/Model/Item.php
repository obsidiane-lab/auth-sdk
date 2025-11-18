<?php

namespace Obsidiane\AuthBundle\Model;

/**
 * @template T of array<string,mixed>
 */
final class Item
{
    /**
     * @param T $attributes
     * @param array<string,mixed>|null $context
     */
    public function __construct(
        public ?string $id,
        public string|array|null $type,
        public array $attributes,
        public ?array $context = null,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     * @return self<array<string,mixed>>
     */
    public static function fromArray(array $data): self
    {
        $id = isset($data['@id']) ? (string) $data['@id'] : null;
        $type = $data['@type'] ?? null;
        $context = null;

        if (isset($data['@context']) && is_array($data['@context'])) {
            $context = $data['@context'];
        }

        unset($data['@id'], $data['@type'], $data['@context']);

        return new self($id, $type, $data, $context);
    }

    /**
     * @return T
     */
    public function data(): array
    {
        return $this->attributes;
    }
}
