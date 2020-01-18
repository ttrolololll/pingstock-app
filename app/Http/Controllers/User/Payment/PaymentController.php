<?php

namespace App\Http\Controllers\User\Payment;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $si = auth()->user()->createSetupIntent();

        if (isset($si['client_secret'])) {
            return JsonResponseHelper::response(
                200,
                'success',
                '',
                [],
                [ 'client_secret' => $si['client_secret'] ]
            );
        }

        return JsonResponseHelper::response(500, false, 'Fail to initiate setup, please try again');
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

        if ($user->hasPaymentMethod()) {
            return JsonResponseHelper::response(200, true, '', [], $user->paymentMethods());
        }

        return JsonResponseHelper::response(200, true, '', [], []);
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

        $user = auth()->user();
        $pm = $user->updateDefaultPaymentMethod($request->post('payment_method'));

        return JsonResponseHelper::response(200, true, 'Payment method added successfully');
    }
}
