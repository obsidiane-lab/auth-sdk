<?php

namespace Obsidiane\AuthBundle\Model;

/**
 * Représente une collection JSON-LD enrichie :
 * - métadonnées @id, @type, @context, totalItems
 * - items JSON-LD (clef "member" ou "items").
 *
 * @template TAttributes of array<string,mixed>
 */
final class Collection
{
    /**
     * @param list<Item<TAttributes>>  $items
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
     *
     * @return self<array<string,mixed>>
     */
    public static function fromArray(array $data): self
    {
        $id = isset($data['@id']) ? (string) $data['@id'] : null;
        $type = $data['@type'] ?? null;
        $totalItems = isset($data['totalItems']) ? (int) $data['totalItems'] : null;
        $context = isset($data['@context']) && is_array($data['@context']) ? $data['@context'] : null;

        $items = [];
        $rows = [];

        if (isset($data['member']) && is_array($data['member'])) {
            $rows = $data['member'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $rows = $data['items'];
        }

        foreach ($rows as $row) {
            if (is_array($row)) {
                $items[] = Item::fromArray($row);
            }
        }

        return new self($items, $id, $type, $totalItems, $context);
    }

    /**
     * @return list<Item<TAttributes>>
     */
    public function all(): array
    {
        return $this->items;
    }
}

