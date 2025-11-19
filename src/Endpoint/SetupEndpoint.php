<?php

namespace Obsidiane\AuthBundle\Endpoint;

use Obsidiane\AuthBundle\Http\HttpClient;

/**
 * Endpoint /api/setup/admin (création de l'administrateur initial).
 */
final class SetupEndpoint
{
    private const PATH_SETUP_INITIAL_ADMIN = '/api/setup/admin';

    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * @param array{email:string,password:string} $input
     *
     * @return array<string,mixed>
     */
    public function createInitialAdmin(array $input): array
    {
        return $this->http->requestJson('POST', self::PATH_SETUP_INITIAL_ADMIN, [
            'headers' => [
                'csrf-token' => $this->http->generateCsrfToken(),
            ],
            'json' => $input,
        ]);
    }
}

