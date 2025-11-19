<?php

namespace Obsidiane\AuthBundle\Auth;

/**
 * Modèles simples pour typer les réponses Auth.
 * Ils reflètent le SDK JS (User / UserRead, etc.) mais restent optionnels :
 * l'API sous-jacente renvoie toujours des tableaux associatifs.
 */
final class Types
{
    /**
     * @param array<string,mixed> $payload
     *
     * @return array{
     *     user: array{
     *         id: int,
     *         email: string,
     *         roles: list<string>,
     *         isEmailVerified?: bool
     *     },
     *     exp: int
     * }
     */
    public static function normalizeLoginResponse(array $payload): array
    {
        $user = $payload['user'] ?? [];
        $roles = [];

        if (isset($user['roles']) && is_array($user['roles'])) {
            foreach ($user['roles'] as $role) {
                $roles[] = (string) $role;
            }
        }

        return [
            'user' => [
                'id' => isset($user['id']) ? (int) $user['id'] : 0,
                'email' => (string) ($user['email'] ?? ''),
                'roles' => $roles,
                'isEmailVerified' => isset($user['isEmailVerified']) ? (bool) $user['isEmailVerified'] : false,
            ],
            'exp' => isset($payload['exp']) ? (int) $payload['exp'] : 0,
        ];
    }
}

