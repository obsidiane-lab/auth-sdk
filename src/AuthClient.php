<?php

namespace Obsidiane\AuthBundle;

use Obsidiane\AuthBundle\Model\Invite as InviteModel;
use Obsidiane\AuthBundle\Model\User as UserModel;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Minimal PHP SDK to interact with Obsidiane Auth endpoints.
 * - Manages a simple cookie jar to carry Set-Cookie between calls.
 * - Requires CSRF token header for state-changing endpoints.
 */
final class AuthClient
{
    private HttpClientInterface $http;
    private string $baseUrl;
    private CookieJar $jar;
    private ?string $origin;

    public function __construct(?HttpClientInterface $http = null, ?string $baseUrl = '')
    {
        $this->http = $http ?? HttpClient::create();
        $this->baseUrl = rtrim((string) $baseUrl, '/');
        $this->jar = new CookieJar();
        $this->origin = $this->computeOrigin($this->baseUrl);
    }

    private function url(string $path): string
    {
        return $this->baseUrl.$path;
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function updateCookies(array $headers): void
    {
        $set = [];
        foreach ($headers as $name => $values) {
            if (strtolower((string) $name) === 'set-cookie') {
                foreach ((array) $values as $val) {
                    $set[] = (string) $val;
                }
            }
        }
        if ($set) {
            $this->jar->addFromSetCookie($set);
        }
    }

    /**
     * Perform a request with current cookies.
     * @param array<string,mixed> $options
     */
    private function request(string $method, string $path, array $options = []): ResponseInterface
    {
        $headers = $options['headers'] ?? [];

        if ($this->origin && !isset($headers['Origin']) && !isset($headers['origin'])) {
            $headers['Origin'] = $this->origin;
        }

        $cookieHeader = $this->jar->toHeader();
        if ($cookieHeader !== '') {
            $headers['Cookie'] = $cookieHeader;
        }

        $options['headers'] = $headers + ['Content-Type' => 'application/json'];
        $response = $this->http->request($method, $this->url($path), $options);
        $this->updateCookies($response->getHeaders(false));
        return $response;
    }

    private function computeOrigin(string $baseUrl): ?string
    {
        if ($baseUrl === '') {
            return null;
        }

        $parts = parse_url($baseUrl);

        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return sprintf('%s://%s%s', $parts['scheme'], $parts['host'], $port);
    }

    /**
     * GET /api/auth/me
     * @return array<string,mixed>
     */
    public function me(): array
    {
        $res = $this->request('GET', '/api/auth/me');
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('me_failed: '.$res->getStatusCode());
        }
        return $res->toArray(false);
    }

    /**
     * Génère un token CSRF stateless compatible avec CsrfRequestValidator.
     * Peut être réutilisé par les appels personnalisés côté consommateur.
     */
    public function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * POST /api/auth/login (CSRF required)
     * @return array<string,mixed>
     */
    public function login(string $email, string $password): array
    {
        $csrf = $this->generateCsrfToken();
        $res = $this->request('POST', '/api/auth/login', [
            'headers' => ['csrf-token' => $csrf],
            'json' => [ 'email' => $email, 'password' => $password ],
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('login_failed: '.$res->getStatusCode());
        }
        return $res->toArray(false);
    }

    /**
     * POST /api/auth/refresh (CSRF optional)
     * @return array<string,mixed>
     */
    public function refresh(?string $csrf = null): array
    {
        $headers = [];
        if ($csrf !== null && $csrf !== '') {
            $headers['csrf-token'] = $csrf;
        }

        $res = $this->request('POST', '/api/auth/refresh', [
            'headers' => $headers,
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('refresh_failed: '.$res->getStatusCode());
        }
        return $res->toArray(false);
    }

    /** POST /api/auth/logout (CSRF required) */
    public function logout(): void
    {
        $csrf = $this->generateCsrfToken();
        $res = $this->request('POST', '/api/auth/logout', [
            'headers' => ['csrf-token' => $csrf],
        ]);
        $code = $res->getStatusCode();
        if ($code !== 204 && $code >= 400) {
            throw new \RuntimeException('logout_failed: '.$code);
        }
    }

    /**
     * POST /api/auth/register (CSRF required)
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function register(array $input): array
    {
        $csrf = $this->generateCsrfToken();
        $res = $this->request('POST', '/api/auth/register', [
            'headers' => ['csrf-token' => $csrf],
            'json' => $input,
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('register_failed: '.$res->getStatusCode());
        }
        return $res->toArray(false);
    }

    /**
     * POST /api/auth/password/forgot (CSRF requis)
     * @return array<string,mixed>
     */
    public function passwordRequest(string $email): array
    {
        $csrf = $this->generateCsrfToken();
        $res = $this->request('POST', '/api/auth/password/forgot', [
            'headers' => ['csrf-token' => $csrf],
            'json' => [ 'email' => $email ],
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('password_request_failed: '.$res->getStatusCode());
        }
        return $res->toArray(false);
    }

    /** POST /api/auth/password/reset (CSRF requis) */
    public function passwordReset(string $token, string $password): void
    {
        $csrf = $this->generateCsrfToken();
        $res = $this->request('POST', '/api/auth/password/reset', [
            'headers' => ['csrf-token' => $csrf],
            'json' => [ 'token' => $token, 'password' => $password ],
        ]);
        $code = $res->getStatusCode();
        if ($code !== 204 && $code >= 400) {
            throw new \RuntimeException('password_reset_failed: '.$code);
        }
    }

    /**
     * POST /api/auth/invite (CSRF required, admin only)
     *
     * @return array<string,mixed>
     */
    public function inviteUser(string $email): array
    {
        $csrf = $this->generateCsrfToken();
        $res = $this->request('POST', '/api/auth/invite', [
            'headers' => ['csrf-token' => $csrf],
            'json' => ['email' => $email],
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('invite_failed: '.$res->getStatusCode());
        }

        return $res->toArray(false);
    }

    /**
     * POST /api/auth/invite/complete (CSRF required)
     *
     * @return array<string,mixed>
     */
    public function completeInvite(string $token, string $password): array
    {
        $csrf = $this->generateCsrfToken();
        $res = $this->request('POST', '/api/auth/invite/complete', [
            'headers' => ['csrf-token' => $csrf],
            'json' => [
                'token' => $token,
                'password' => $password,
                'confirmPassword' => $password,
            ],
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('invite_complete_failed: '.$res->getStatusCode());
        }

        return $res->toArray(false);
    }

    // --- ApiPlatform helpers (User & Invite resources) ---

    /**
     * GET /api/users/me (ApiResource<User>)
     */
    public function currentUserResource(): UserModel
    {
        $res = $this->request('GET', '/api/users/me', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('users_me_failed: '.$res->getStatusCode());
        }

        $data = $res->toArray(false);

        return UserModel::fromArray($data);
    }

    /**
     * GET /api/invite_users
     *
     * @return list<InviteModel>
     */
    public function listInvites(): array
    {
        $res = $this->request('GET', '/api/invite_users', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('invite_list_failed: '.$res->getStatusCode());
        }

        $data = $res->toArray(false);

        $invites = [];
        foreach ($data as $row) {
            if (is_array($row)) {
                $invites[] = InviteModel::fromArray($row);
            }
        }

        return $invites;
    }

    /**
     * GET /api/invite_users/{id}
     */
    public function getInvite(int $id): InviteModel
    {
        $res = $this->request('GET', '/api/invite_users/'.$id, [
            'headers' => ['Accept' => 'application/json'],
        ]);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('invite_get_failed: '.$res->getStatusCode());
        }

        $data = $res->toArray(false);

        return InviteModel::fromArray($data);
    }
}
