<?php

namespace App\Services\Xendits\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XenditService
{
    protected PendingRequest $client;

    /** Default context (optional) */
    protected ?string $defaultForUserId = null;
    protected ?string $defaultSplitRuleId = null;

    public function __construct(
        ?string $forUserId = null,
        ?string $splitRuleId = null,
    ) {
        $this->defaultForUserId  = $forUserId;
        $this->defaultSplitRuleId = $splitRuleId;

        $this->client = Http::baseUrl(config('xendit.base_url'))
            ->timeout((int) config('xendit.timeout', 15))
            ->withHeaders([
                'api-version'  => config('xendit.api_version', '2024-11-11'),
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                // catatan: header kustom akan diinject per-request / via context
            ])
            ->withBasicAuth((string) config('xendit.api_key'), '')
            ->retry(
                (int) config('xendit.retry.times', 2),
                (int) config('xendit.retry.sleep', 200)
            );
    }

    /**
     * Set default context untuk seluruh request berikutnya dari instance ini.
     * Gunakan saat transaksi dilakukan atas nama subaccount + menerapkan split rule.
     */
    public function setContext(?string $forUserId = null, ?string $splitRuleId = null): static
    {
        $this->defaultForUserId  = $forUserId;
        $this->defaultSplitRuleId = $splitRuleId;
        return $this;
    }

    /**
     * Clone client dengan header opsional (gabung default context + override per-request).
     */
    protected function withOptionalHeaders(
        ?string $forUserId = null,
        ?string $splitRuleId = null
    ): PendingRequest {
        $headers = [];

        // pakai override per-request > kalau null jatuh ke default context
        $effectiveForUserId  = $forUserId   ?? $this->defaultForUserId;
        $effectiveSplitRuleId = $splitRuleId ?? $this->defaultSplitRuleId;

        if ($effectiveForUserId) {
            $headers['for-user-id'] = $effectiveForUserId;
        }

        if ($effectiveSplitRuleId) {
            $headers['with-split-rule'] = $effectiveSplitRuleId;
        }

        return (clone $this->client)->withHeaders($headers);
    }

    /** -------- Core HTTP helpers (digunakan oleh turunan) -------- */

    protected function get(
        string $path,
        array $query = [],
        ?string $forUserId = null,
        ?string $splitRuleId = null
    ): array {
        $req = $this->withOptionalHeaders($forUserId, $splitRuleId);
        return $this->send('GET', $path, $query, [], $req);
    }

    protected function post(
        string $path,
        array $payload = [],
        array $query = [],
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $splitRuleId = null
    ): array {
        $req = $this->withOptionalHeaders($forUserId, $splitRuleId)
            ->withHeaders([
                'Idempotency-Key' => $idempotencyKey ?? Str::uuid()->toString(),
            ]);

        return $this->send('POST', $path, $query, $payload, $req);
    }

    protected function request(
        string $method,
        string $path,
        array $query = [],
        array $payload = [],
        ?string $forUserId = null,
        ?string $splitRuleId = null
    ): array {
        $req = $this->withOptionalHeaders($forUserId, $splitRuleId);
        return $this->send($method, $path, $query, $payload, $req);
    }

    /** -------- Internal sender + logging -------- */

    protected function send(
        string $method,
        string $path,
        array $query = [],
        array $payload = [],
        ?PendingRequest $client = null
    ): array {
        $client     = $client ?: $this->client;
        $url        = rtrim((string) config('xendit.base_url'), '/') . $path;
        $headers    = $this->buildEffectiveHeaders($client);
        $bodyForLog = $payload;
        $startedAt  = microtime(true);

        try {
            $response = $client->withQueryParameters($query)->send($method, $path, [
                'json' => $payload ?: null,
            ]);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $json       = $response->json();

            $this->logHttp([
                'direction'  => 'response',
                'method'     => $method,
                'url'        => $url,
                'status'     => $response->status(),
                'durationMs' => $durationMs,
                'headers'    => $this->sanitizeHeaders($response->headers()),
                'body'       => $json,
            ]);

            $response->throw();
            return is_array($json) ? $json : (array) $json;
        } catch (RequestException $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $this->logHttp([
                'direction'  => 'request',
                'method'     => $method,
                'url'        => $url,
                'status'     => optional($e->response)->status(),
                'durationMs' => $durationMs,
                'headers'    => $this->sanitizeHeaders($headers),
                'query'      => $query,
                'body'       => $bodyForLog,
                'error'      => $e->getMessage(),
                'response'   => $e->response?->json(),
            ], true);

            $body = $e->response ? $e->response->json() : null;
            report($e);

            throw new \RuntimeException(json_encode([
                'service' => 'xendit',
                'status'  => optional($e->response)->status(),
                'error'   => data_get($body, 'error') ?? data_get($body, 'message') ?? $e->getMessage(),
                'details' => $body ?? null,
            ]), (int) optional($e->response)->status() ?: 500);
        } finally {
            // Always log outbound request (best effort, once)
            $this->logHttp([
                'direction' => 'request',
                'method'    => $method,
                'url'       => $url,
                'headers'   => $this->sanitizeHeaders($headers),
                'query'     => $query,
                'body'      => $bodyForLog,
            ]);
        }
    }

    /** -------- Utilities: logging & masking -------- */

    protected function logHttp(array $payload, bool $isError = false): void
    {
        if (!config('xendit.logging.enabled')) {
            return;
        }

        $channel   = config('xendit.logging.channel', 'xendit');
        $level     = $isError ? 'error' : config('xendit.logging.level', 'info');
        $logBody   = (bool) config('xendit.logging.log_body', false);
        $maxLength = (int) config('xendit.logging.max_length', 4000);

        if (!$logBody) {
            unset($payload['body'], $payload['response']);
        } else {
            if (isset($payload['body'])) {
                $payload['body'] = $this->maskSensitive($payload['body']);
                $payload['body'] = $this->truncate($payload['body'], $maxLength);
            }
            if (isset($payload['response'])) {
                $payload['response'] = $this->maskSensitive($payload['response']);
                $payload['response'] = $this->truncate($payload['response'], $maxLength);
            }
        }

        if (isset($payload['headers'])) $payload['headers'] = $this->truncate($payload['headers'], $maxLength);
        if (isset($payload['query']))   $payload['query']   = $this->truncate($payload['query'], $maxLength);

        Log::channel($channel)->{$level}('xendit.http', $payload);
    }

    protected function buildEffectiveHeaders(PendingRequest $req): array
    {
        $headers = $req->getOptions()['headers'] ?? [];

        // Mark Basic auth (mask API key in logs)
        $apiKey = (string) config('xendit.api_key');
        $headers['Authorization'] = 'Basic ' . $this->mask('***', $apiKey);

        return $this->sanitizeHeaders($headers);
    }

    protected function sanitizeHeaders(array $headers): array
    {
        $masked = [];
        foreach ($headers as $key => $value) {
            $k = strtolower((string) $key);
            $v = is_array($value) ? implode(';', $value) : (string) $value;

            if (in_array($k, ['authorization', 'proxy-authorization', 'x-api-key', 'api-key'])) {
                $masked[$key] = $this->maskHeader($v);
            } else {
                $masked[$key] = $v;
            }
        }
        return $masked;
    }

    protected function maskHeader(string $value): string
    {
        if (str_starts_with(strtolower($value), 'basic ')) {
            return 'Basic ******';
        }
        if (str_starts_with(strtolower($value), 'bearer ')) {
            return 'Bearer ******';
        }
        return '******';
    }

    protected function mask(string $prefix, string $secret): string
    {
        if ($secret === '') return '******';
        $tail = Str::of($secret)->substr(-4);
        return $prefix . $tail;
    }

    protected function maskSensitive(array|string|null $data)
    {
        if (is_null($data)) return null;

        $fields = (array) config('xendit.logging.mask_fields', []);
        $walker = function (&$value, $key) use ($fields) {
            if (is_string($key) && in_array(strtolower($key), array_map('strtolower', $fields))) {
                $value = '******';
            }
        };

        if (is_array($data)) {
            array_walk_recursive($data, $walker);
            return $data;
        }

        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            array_walk_recursive($decoded, $walker);
            return json_encode($decoded);
        }

        return $data;
    }

    protected function truncate(mixed $data, int $limit)
    {
        if (is_string($data)) {
            return Str::limit($data, $limit);
        }

        if (is_array($data) || is_object($data)) {
            $str = json_encode($data);
            if (is_string($str)) {
                return Str::limit($str, $limit);
            }
        }

        return $data;
    }
}
