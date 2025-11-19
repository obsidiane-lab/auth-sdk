<?php

namespace Obsidiane\AuthBundle\Endpoint;

use Obsidiane\AuthBundle\Http\HttpClient;

/**
 * Endpoints /api/users* (ressource User exposée par API Platform).
 * Aligné sur le SDK JS : liste et détail renvoient la structure JSON-LD brute.
 */
final class UsersEndpoint
{
    private const PATH_USERS = '/api/users';

    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * GET /api/users
     *
     * @return array<string,mixed> JSON-LD collection (équivalent de Collection<UserRead> côté JS)
     * @throws \JsonException
     */
    public function list(): array
    {
        return $this->http->requestJson('GET', self::PATH_USERS);
    }

    /**
     * GET /api/users/{id}
     *
     * @return array<string,mixed> JSON-LD item (équivalent de UserRead côté JS)
     * @throws \JsonException
     */
    public function get(int $id): array
    {
        return $this->http->requestJson('GET', self::PATH_USERS.'/'.$id);
    }

    /**
     * DELETE /api/users/{id}
     */
    public function delete(int $id): void
    {
        $this->http->requestJson('DELETE', self::PATH_USERS.'/'.$id);
    }
}

