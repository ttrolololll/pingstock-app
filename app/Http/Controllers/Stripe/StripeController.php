<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('cashier.secret'));
    }
}
