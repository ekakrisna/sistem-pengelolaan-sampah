<?php

namespace App\Services\Xendits\Customer;

use App\Data\Xendit\Platform\CreateAccount\CreateAccountRequestData;
use App\Data\Xendit\Platform\ListAccounts\ListAccountsQueryData;
use App\Services\Xendits\Http\XenditService;
use Illuminate\Support\Arr;

class XenPlatformService extends XenditService
{

    public function getAccounts(ListAccountsQueryData $query): array
    {
        $q = $query->toQuery();
        $qs = $this->buildQueryString($q);
        $res = $this->get('/v2/accounts', $qs);
        return $res;
    }

    public function createAccount(CreateAccountRequestData $payload): array
    {
        $res = $this->post('/v2/accounts', $payload->toPayload());
        return $res;
    }
}
