<?php

namespace Obsidiane\AuthBundle\Model;

final class User
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public int $id,
        public string $email,
        public array $roles,
        public bool $isEmailVerified = false,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $roles = [];
        if (isset($data['roles']) && is_array($data['roles'])) {
            foreach ($data['roles'] as $role) {
                $roles[] = (string) $role;
            }
        }

        return new self(
            isset($data['id']) ? (int) $data['id'] : 0,
            (string) ($data['email'] ?? ''),
            $roles,
            isset($data['isEmailVerified']) ? (bool) $data['isEmailVerified'] : false,
        );
    }
}

