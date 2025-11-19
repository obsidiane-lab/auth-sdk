<?php

namespace Obsidiane\AuthBundle\Endpoint;

use Obsidiane\AuthBundle\Http\HttpClient;

/**
 * Endpoints /api/invite_users* (ressource InviteUser exposée par API Platform).
 */
final class InvitesEndpoint
{
    private const PATH_INVITE_USERS = '/api/invite_users';

    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * GET /api/invite_users
     *
     * @return array<string,mixed> JSON-LD collection (équivalent de Collection<InviteUserRead> côté JS)
     */
    public function list(): array
    {
        return $this->http->requestJson('GET', self::PATH_INVITE_USERS);
    }

    /**
     * GET /api/invite_users/{id}
     *
     * @return array<string,mixed> JSON-LD item (équivalent de InviteUserRead côté JS)
     */
    public function get(int $id): array
    {
        return $this->http->requestJson('GET', self::PATH_INVITE_USERS.'/'.$id);
    }
}

