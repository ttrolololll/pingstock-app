<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $user = User::find(1);
//        $user->createAsStripeCustomer();
        $pm = $user->paymentMethods();
//        $user->updateDefaultPaymentMethodFromStripe();

//        $user->newSubscription('default', 'plan_GYL5br2Pw6NUol')->create('pm_1G1FUCEv9JgIFS5nUF27LjjS');


        return $pm[0]->asStripePaymentMethod();
    }
}
