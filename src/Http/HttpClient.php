<?php

namespace Obsidiane\AuthBundle\Http;

use Obsidiane\AuthBundle\Exception\ApiErrorException;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\BrowserKit\Response as BrowserResponse;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;

/**
 * Client HTTP bas-niveau utilisé par le SDK.
 * - Gère un CookieJar interne (via HttpBrowser) pour persister les cookies
 *   entre les requêtes (access/refresh tokens).
 * - Normalise les en-têtes et décode systématiquement les réponses JSON.
 */
final class HttpClient
{
    private HttpBrowser $browser;

    private string $baseUrl;

    private ?string $origin;

    /**
     * @var array<string,string>
     */
    private array $defaultHeaders;

    public function __construct(string $baseUrl, array $defaultHeaders = [], ?int $timeoutMs = null, ?string $origin = null)
    {
        if ($baseUrl === '') {
            throw new \InvalidArgumentException('baseUrl is required');
        }

        $clientOptions = [];
        if ($timeoutMs !== null && $timeoutMs > 0) {
            $clientOptions['timeout'] = $timeoutMs / 1000;
        }

        $this->browser = new HttpBrowser(SymfonyHttpClient::create($clientOptions));
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->origin = $origin;
        $this->defaultHeaders = $defaultHeaders;
    }

    /**
     * @param array<string,mixed> $options
     *
     * @return array<string,mixed>
     */
    public function requestJson(string $method, string $path, array $options = []): array
    {
        $url = $this->baseUrl.$path;
        $headers = $this->buildHeaders($options['headers'] ?? []);

        $server = $this->buildServerHeaders($headers);
        $content = $options['body'] ?? null;

        if (isset($options['json'])) {
            $content = json_encode($options['json'], JSON_THROW_ON_ERROR);
        }

        $this->browser->request($method, $url, [], [], $server, $content ?? '');

        /** @var BrowserResponse $response */
        $response = $this->browser->getResponse();

        return $this->decodeResponse($response);
    }

    /**
     * Génère un token CSRF stateless compatible avec CsrfRequestValidator.
     */
    public function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param array<string,string> $requestHeaders
     *
     * @return array<string,string>
     */
    private function buildHeaders(array $requestHeaders = []): array
    {
        $headers = [
            'Accept' => 'application/ld+json, application/json',
        ];

        foreach ($this->defaultHeaders as $name => $value) {
            $headers[$name] = (string) $value;
        }

        foreach ($requestHeaders as $name => $value) {
            $headers[$name] = (string) $value;
        }

        // Pour la protection CSRF stateless, le backend vérifie Origin/Referer.
        // On simule donc une origine navigateur basée sur baseUrl si aucune
        // entête Origin n'est déjà présente.
        if ($this->origin !== null && !isset($headers['Origin']) && !isset($headers['origin'])) {
            $headers['Origin'] = $this->origin;
        }

        return $headers;
    }

    /**
     * @param array<string,string> $headers
     *
     * @return array<string,string>
     */
    private function buildServerHeaders(array $headers): array
    {
        $server = [];

        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            if ($key === 'CONTENT_TYPE') {
                $server['CONTENT_TYPE'] = $value;
            } elseif ($key === 'ACCEPT') {
                $server['HTTP_ACCEPT'] = $value;
            } else {
                $server['HTTP_'.$key] = $value;
            }
        }

        return $server;
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeResponse(BrowserResponse $response): array
    {
        $status = 0;
        if (method_exists($response, 'getStatusCode')) {
            $status = (int) $response->getStatusCode();
        } elseif (method_exists($response, 'getStatus')) {
            $status = (int) $response->getStatus();
        }

        if ($status === 204) {
            return [];
        }

        $raw = '';
        try {
            $raw = $response->getContent();
        } catch (\Throwable) {
            $raw = '';
        }

        $body = [];

        if ($raw !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $body = is_array($decoded) ? $decoded : [];
            } catch (\Throwable) {
                $decoded = json_decode($raw, true);
                $body = is_array($decoded) ? $decoded : ['raw' => $raw];
            }
        }

        if ($status >= 400) {
            throw ApiErrorException::fromPayload($status, $body);
        }

        return $body;
    }
}
