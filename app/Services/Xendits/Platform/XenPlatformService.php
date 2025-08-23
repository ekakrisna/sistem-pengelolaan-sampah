<?php

namespace App\Services\Xendits\Platform;

use App\Data\Xendit\Platform\CreateAccount\CreateAccountRequestData;
use App\Data\Xendit\Platform\ListAccounts\ListAccountsQueryData;
use App\Services\Xendits\Http\XenditService;

class XenPlatformService extends XenditService
{

    public function getAccounts(ListAccountsQueryData $query): array
    {
        $q = $query->toQuery();
        $qs = $this->buildQueryString($q);
        $res = $this->get('/v2/accounts', $qs);
        return $res;
    }

    public function getAccount(string $id): array
    {
        $res = $this->get("/v2/accounts/{$id}");
        return $res;
    }

    public function createAccount(CreateAccountRequestData $payload): array
    {
        $res = $this->post('/v2/accounts', $payload->toPayload());
        return $res;
    }
}
