<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $user;

    public function __construct(
        protected Request $request,
    ) {
        $this->user = UserData::from($request->user());
    }
}
