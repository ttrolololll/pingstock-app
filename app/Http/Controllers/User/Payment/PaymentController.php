<?php

namespace App\Http\Controllers\User\Payment;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class PaymentController extends Controller
{
    /**
     * Creates a Stripe Setup Intent, typically for when adding new payment cards
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newSetupIntent(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // create as Stripe customer if not already is
        if (! $user->stripe_id) {
            $user->createAsStripeCustomer();

            // check if is successful by checking if user stripe_id is set
            if (! $user->stripe_id) {
                return JsonResponseHelper::internal("Unable to assert valid payment customer");
            }
        }

        $si = $user->createSetupIntent();

        if (isset($si['client_secret'])) {
            return JsonResponseHelper::ok('', [], [ 'client_secret' => $si['client_secret'] ]);
        }

        return JsonResponseHelper::internal('Fail to initiate setup, please try again');
    }

    /**
     * Get a list of user payment cards
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentMethods(Request $request)
    {
        $user = auth()->user();

        // use Stripe directly, because Laravel $user->paymentMethods() returns empty
        Stripe::setApiKey(config('cashier.secret'));

        try {
            $paymentMethods = PaymentMethod::all([
                'customer' =>  $user->stripe_id,
                'type' => 'card',
            ]);
        } catch (\Exception $e) {
            Log::debug('[PaymentController.paymentMethods] ' . $e->getMessage(), $e->getTrace());
            return JsonResponseHelper::internal('Payment API key not found');
        }

        if ($user->hasPaymentMethod()) {
            return JsonResponseHelper::ok('', [], $paymentMethods->data);
        }

        return JsonResponseHelper::ok();
    }

    /**
     * Add payment method to user by payment_method identifier
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPaymentMethod(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'payment_method' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, 'Invalid payment method');
        }

        /** @var User $user */
        $user = auth()->user();

        // create stripe customer if not already one
        if (! $user->stripe_id) {
            $user->createAsStripeCustomer();
        }

        $user->updateDefaultPaymentMethod($request->post('payment_method'));

        return JsonResponseHelper::response(200, true, 'Payment method added successfully');
    }

    /**
     * Delete All Payment Methods
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAllPaymentMethods(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $user->deletePaymentMethods();

        return JsonResponseHelper::response(200, true, 'Payment method removed successfully');
    }
}
