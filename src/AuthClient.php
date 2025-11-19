<?php

namespace Obsidiane\AuthBundle;

use Obsidiane\AuthBundle\Endpoint\AuthEndpoint;
use Obsidiane\AuthBundle\Endpoint\InvitesEndpoint;
use Obsidiane\AuthBundle\Endpoint\SetupEndpoint;
use Obsidiane\AuthBundle\Endpoint\UsersEndpoint;
use Obsidiane\AuthBundle\Http\HttpClient;

/**
 * Client PHP haut-niveau pour consommer l'API Obsidiane Auth.
 *
 * Alignement avec le SDK JS :
 * - $client->auth()    : endpoints /api/auth/*
 * - $client->users()   : endpoints /api/users*
 * - $client->invites() : endpoints /api/invite_users*
 * - $client->setup()   : endpoint  /api/setup/admin
 */
final class AuthClient
{
    private HttpClient $http;

    private AuthEndpoint $auth;

    private UsersEndpoint $users;

    private InvitesEndpoint $invites;

    private SetupEndpoint $setup;

    /**
     * @param array<string,string> $defaultHeaders
     */
    public function __construct(
        string $baseUrl,
        array $defaultHeaders = [],
        ?int $timeoutMs = null,
        ?string $origin = null,
    ) {
        $this->http = new HttpClient($baseUrl, $defaultHeaders, $timeoutMs, $origin);
        $this->auth = new AuthEndpoint($this->http);
        $this->users = new UsersEndpoint($this->http);
        $this->invites = new InvitesEndpoint($this->http);
        $this->setup = new SetupEndpoint($this->http);
    }

    public function auth(): AuthEndpoint
    {
        return $this->auth;
    }

    public function users(): UsersEndpoint
    {
        return $this->users;
    }

    public function invites(): InvitesEndpoint
    {
        return $this->invites;
    }

    public function setup(): SetupEndpoint
    {
        return $this->setup;
    }

    /**
     * Génère un token CSRF compatible avec les endpoints protégés.
     */
    public function generateCsrfToken(): string
    {
        return $this->http->generateCsrfToken();
    }
}
