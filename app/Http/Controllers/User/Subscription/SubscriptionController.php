<?php

namespace App\Http\Controllers\User\Subscription;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

class SubscriptionController extends Controller
{
    public function currentSubscription(Request $request)
    {
        $subscription = Subscription::where('user_id', auth()->user()->id)->first();

        if (! $subscription) {
            return JsonResponseHelper::response(404, false, 'User does not have a subscription yet');
        }

        return JsonResponseHelper::response(200, true, '', [], $subscription);
    }

    public function subscribe(Request $request)
    {
        $user = User::find(1);
//        $user->createAsStripeCustomer();
        $pm = $user->paymentMethods();
//        $user->updateDefaultPaymentMethodFromStripe();

//        $user->newSubscription('default', 'plan_GYL5br2Pw6NUol')->create('pm_1G1FUCEv9JgIFS5nUF27LjjS');


        return $pm[0]->asStripePaymentMethod();
    }

    public function cancelSubscription(Request $request)
    {

    }
}
