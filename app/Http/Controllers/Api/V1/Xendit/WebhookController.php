<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Http\Controllers\Controller;
use App\Services\Xendits\Webhook\XenditWebhookService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected XenditWebhookService $webhooks
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {

            $result = $this->webhooks->handle($request, function (array $payload) {
                // TODO: business logic kamu di sini
                // contoh:
                // if (($payload['event'] ?? '') === 'payment.succeeded') { ... }
                return true;
            });
            return $this->successResponse($result, 'Webhook processed.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }
}
