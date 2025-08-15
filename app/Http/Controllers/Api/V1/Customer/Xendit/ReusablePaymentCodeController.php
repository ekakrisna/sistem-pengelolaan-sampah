<?php

namespace App\Http\Controllers\Api\V1\Customer\Xendit;

use App\Data\Xendit\Pay\PaymentsApiReusablePaymentCodeData;
use App\Http\Controllers\Controller;
use App\Services\Xendits\PaymentRequest\ReusablePaymentCodeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReusablePaymentCodeController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReusablePaymentCodeService $service
    ) {}

    public function createNoAmount(PaymentsApiReusablePaymentCodeData $dto): JsonResponse
    {
        try {
            // (Aktifkan validasi otomatis Spatie Data lewat pipes. Jika belum, kamu bisa uncomment baris di bawah)
            // $dto::validate($request->all());

            // ambil context header/query opsional
            // $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            // $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            // set header context untuk request ini
            // $this->service->setContext($forUserId, $splitRuleId);

            // kirim ke Xendit
            $payload = $dto->toPayload();
            // dd($payload);
            $res = $this->service->createNoAmount(
                $payload['reference_id'],
                $payload['channel_code'],
                $payload['country'],
                $payload['currency']
            );
            // dd($res);

            return $this->successResponse($res, 'Reusable payment code created.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            // dd($name, $message, $code);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }

    public function createWithAmount(PaymentsApiReusablePaymentCodeData $dto): JsonResponse
    {
        try {
            $payload = $dto->toPayload();

            $data = $this->service->createWithAmount(
                $payload['reference_id'],
                $payload['channel_code'],
                $payload['request_amount'],
                $payload['channel_properties'],
                $payload['country'],
                $payload['currency']
            );

            return $this->successResponse($data, 'Reusable payment code created (with amount).');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            return $this->errorResponse($name, $message, $code);
        }
    }
}
