<?php

namespace App\Services\Xendits\Webhook;

use App\Services\Xendits\Http\XenditService;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

class XenditWebhookService extends XenditService
{
    /**
     * Tangani webhook:
     * - verifikasi signature/token
     * - idempotency
     * - eksekusi processor (opsional)
     * - log inbound via logHttp()
     *
     * @param  Request       $request   request Laravel mentah (untuk baca header/body)
     * @param  callable|null $processor fn(array $payload, Request $req): mixed
     * @return array { ok: bool, idempotency_key: string, result: mixed|null }
     */
    public function handle(Request $request, ?callable $processor = null): array
    {
        $raw     = (string) $request->getContent();
        $headers = $this->collectHeaders($request);

        // Log awal: inbound webhook (raw)
        $this->logHttp([
            'direction' => 'webhook_inbound',
            'method'    => $request->getMethod(),
            'url'       => (string) $request->fullUrl(),
            'headers'   => $this->sanitizeHeaders($headers),
            'body'      => $this->decodeIfJson($raw),
        ]);

        // Verifikasi signature/token
        if (!$this->verify($request, $raw)) {
            $this->logHttp([
                'direction' => 'webhook_inbound',
                'method'    => $request->getMethod(),
                'url'       => (string) $request->fullUrl(),
                'error'     => 'Invalid webhook signature/token',
            ], true);

            throw new HttpException(401, 'Invalid webhook signature/token');
        }

        // Parse payload
        $payload = $this->parsePayload($raw);

        // Idempotency
        $idemKey = $this->resolveIdempotencyKey($request, $payload, $raw);
        $this->guardIdempotency($idemKey);

        // Proses bisnis (opsional)
        $result = null;
        if ($processor) {
            $result = $processor($payload, $request);
        }

        // Log selesai
        $this->logHttp([
            'direction' => 'webhook_outbound',
            'method'    => $request->getMethod(),
            'url'       => (string) $request->fullUrl(),
            'status'    => 200,
            'idempotency_key' => $idemKey,
            'body'      => $payload,
        ]);

        return [
            'ok'              => true,
            'idempotency_key' => $idemKey,
            'result'          => $result,
        ];
    }

    /**
     * Verifikasi token/signature webhook.
     * - Shared token (default): header x-callback-token = config('xendit.webhook.token')
     * - HMAC (opsional): header configurable (default: x-endpoint-signature-hmac-sha256)
     */
    public function verify(Request $request, string $rawBody): bool
    {
        // 1) Shared Token (umum di Xendit)
        $expectedToken = (string) config('xendit.webhook.token', '');
        if ($expectedToken !== '') {
            $gotToken = $this->headerFirst($request, ['x-callback-token', 'X-Callback-Token']);
            if (!$this->hashEquals($expectedToken, (string) $gotToken)) {
                return false;
            }
        }

        // 2) HMAC (opsional; jika diset)
        $hmacSecret = (string) config('xendit.webhook.hmac_secret', '');
        if ($hmacSecret !== '') {
            $headerName = (string) config('xendit.webhook.signature_header', 'x-endpoint-signature-hmac-sha256');
            $sig        = (string) $request->headers->get($headerName, '');
            if ($sig === '') {
                return false;
            }

            $calcHex = hash_hmac('sha256', $rawBody, $hmacSecret);
            $calcB64 = base64_encode(hex2bin($calcHex));

            // Terima hex atau base64 (bergantung konfigurasi endpoint)
            if (!($this->hashEquals($sig, $calcHex) || $this->hashEquals($sig, $calcB64))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Kunci idempotency untuk mencegah double processing.
     * Gunakan Cache::lock() dengan TTL dari config.
     */
    protected function guardIdempotency(string $key): void
    {
        if (!config('xendit.webhook.idempotency.enabled', true)) {
            return;
        }
        $ttl   = (int) config('xendit.webhook.idempotency.ttl', 1800);
        $store = (string) config('xendit.webhook.idempotency.cache_store', '');

        /** @var CacheFactory $cache */
        $cache = app(CacheFactory::class);
        /** @var \Illuminate\Cache\Repository $repo */
        $repo  = $store !== '' ? $cache->store($store) : $cache->store();

        // Use locks if the store supports it (Redis/Memcached/DynamoDB)
        if (method_exists($repo, 'getStore') && $repo->getStore() instanceof LockProvider) {
            /** @var \Illuminate\Contracts\Cache\Lock $lock */
            $lock = $repo->lock("xendit:webhook:{$key}", $ttl);
            if (!$lock->get()) {
                throw new HttpException(409, 'Duplicate webhook (idempotent)');
            }
            register_shutdown_function(static function () use ($lock) {
                try {
                    $lock->release();
                } catch (\Throwable) {
                }
            });
            return;
        }

        // Fallback for drivers without locking (file/array/database)
        $idemKey = "xendit:webhook:{$key}";
        $stored  = $repo->add($idemKey, 1, $ttl); // int seconds in Laravel 12
        if (!$stored) {
            throw new HttpException(409, 'Duplicate webhook (idempotent)');
        }
    }

    /**
     * Tentukan idempotency key dari:
     * - header: x-idempotency-key | idempotency-key
     * - payload: data.id | id | payment_request_id
     * - fallback: sha1(rawBody)
     */
    protected function resolveIdempotencyKey(Request $request, array $payload, string $rawBody): string
    {
        $h = $request->headers;

        $candidates = array_filter([
            $h->get('x-idempotency-key'),
            $h->get('Idempotency-Key'),
            Arr::get($payload, 'data.id'),
            Arr::get($payload, 'id'),
            Arr::get($payload, 'payment_request_id'),
        ], static fn($v) => is_string($v) && $v !== '');

        $key = $candidates[0] ?? sha1($rawBody);

        return (string) $key;
    }

    /** ---------- helpers ---------- */
    protected function collectHeaders(Request $request): array
    {
        $all = [];
        foreach ($request->headers->all() as $k => $vals) {
            $all[$k] = implode(';', $vals);
        }
        return $all;
    }

    protected function decodeIfJson(string $raw): array|string|null
    {
        $json = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : $raw;
    }

    protected function parsePayload(string $raw): array
    {
        $json = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : [];
    }

    protected function headerFirst(Request $request, array $names): ?string
    {
        foreach ($names as $n) {
            $v = $request->headers->get($n);
            if ($v !== null && $v !== '') {
                return $v;
            }
        }
        return null;
    }

    protected function hashEquals(string $known, string $user): bool
    {
        // constant-time compare
        if ($known === '' || $user === '') {
            return false;
        }
        return hash_equals($known, $user);
    }
}
