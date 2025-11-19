<?php

namespace Obsidiane\AuthBundle\Model;

/**
 * @template T of array<string,mixed>
 */
final class Collection
{
    /**
     * @param list<Item<T>> $items
     * @param array<string,mixed>|null $context
     */
    public function __construct(
        public array $items,
        public ?string $id = null,
        public string|array|null $type = null,
        public ?int $totalItems = null,
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
        $totalItems = isset($data['totalItems']) ? (int) $data['totalItems'] : null;
        $context = null;

        if (isset($data['@context']) && is_array($data['@context'])) {
            $context = $data['@context'];
        }

        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $row) {
                if (is_array($row)) {
                    $items[] = Item::fromArray($row);
                }
            }
        }

        return new self($items, $id, $type, $totalItems, $context);
    }

    /**
     * @return list<Item<T>>
     */
    public function all(): array
    {
        return $this->items;
    }
}

