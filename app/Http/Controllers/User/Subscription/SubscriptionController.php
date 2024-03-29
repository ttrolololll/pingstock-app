<?php

namespace App\Http\Controllers\User\Subscription;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\StockAlertRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Cashier\Subscription;
use Stripe\Exception\InvalidRequestException;
use Stripe\Stripe;

class SubscriptionController extends Controller
{
    protected static $logTag = 'User\Subscription\SubscriptionController';
    protected static $defaultSubscriptionName = 'PingStock Invest';

    /**
     * Get current subscription
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentSubscription(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // get subscription from db
        $dbData = Subscription::where([
            ['user_id', $user->id],
            ['stripe_status', 'active'],
        ])->first();

        if (! $dbData) {
            return JsonResponseHelper::notFound('User does not have a subscription yet');
        }

        // get stripe subscription
        Stripe::setApiKey(config('cashier.secret'));
        $stripeData = \Stripe\Subscription::retrieve($dbData->stripe_id);

        // update db subscription status if differs
        if ($dbData->stripe_status != $stripeData->status) {
            $dbData->stripe_status = $stripeData->status;
            $dbData->save();
        }

        // get total active stock alert rules
        $rules = StockAlertRule::where('user_id', $user->id)
            ->whereNull('triggered_at')
            ->count();

        $stripeData->db_data = $dbData;
        $stripeData->usage = $rules;

        return JsonResponseHelper::response(200, true, '', [], $stripeData);
    }

    /**
     * Subscribe to a plan
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|Subscription
     */
    public function subscribe(Request $request)
    {
        $validator = $this->subscribeRequestValidator($request->post());

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, '', $validator->errors());
        }

        /** @var User $user */
        $user = auth()->user();
        $plan = $request->post('plan');
        $subscriptions = $user->subscriptions()->get();

        // return if subscription already exists for user
        if (count($subscriptions) > 0) {
            return JsonResponseHelper::response(400, false, 'Already subscribed', [], []);
        }

        // create stripe customer if not already one
        if (! $user->stripe_id) {
            $user->createAsStripeCustomer();
        }

        // update default payment if exists
        if (! $user->hasPaymentMethod()) {
            $user->updateDefaultPaymentMethodFromStripe();
        }

        $pm = $user->defaultPaymentMethod();

        if (! $pm) {
            return JsonResponseHelper::response(400, false, 'No available payment method', [], []);
        }

        try {
            $subscription = $user->newSubscription(self::$defaultSubscriptionName, $plan)
                ->withMetadata(['stock_alerts' => config('subscriber_stock_alerts', 20)])
                ->create($pm->id);
        } catch (InvalidRequestException $e) {
            return JsonResponseHelper::response(400, false, $e->getMessage(), [], []);
        } catch (\Exception $e) {
            Log::notice('[' . self::$logTag . '.subscribe] Payment failure: ' . $e->getMessage(), $e->getTrace());
            return JsonResponseHelper::response(500, false, 'Payment failure', [], []);
        }

        return $subscription;
    }

    /**
     * Cancel Subscription
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'subscription_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, '', $validator->errors());
        }

        /** @var User $user */
        $user = auth()->user();

        /** @var Subscription $subscription */
        $subscription = Subscription::where([
            ['user_id', $user->id],
            ['stripe_id', $request->post('subscription_id')],
        ])->first();

        if (! $subscription) {
            return JsonResponseHelper::response(400, false, 'Subscription does not exist');
        }

        $subscription->cancel();

        return JsonResponseHelper::response(200, true, 'Unsubscribed');
    }

    /**
     * Resume Subscription
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resumeSubscription(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'subscription_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, '', $validator->errors());
        }

        /** @var User $user */
        $user = auth()->user();

        /** @var Subscription $subscription */
        $subscription = Subscription::where([
            ['user_id', $user->id],
            ['stripe_id', $request->post('subscription_id')],
        ])->first();

        if (! $subscription) {
            return JsonResponseHelper::response(400, false, 'Subscription does not exist');
        }

        if (! $subscription->ends_at) {
            return JsonResponseHelper::response(400, false, 'Subscription is active');
        }

        try {
            $subscription->resume();
        } catch (\LogicException $e) {
            return JsonResponseHelper::response(400, false, 'Cancelled subscription passed grace period');
        }

        return JsonResponseHelper::response(200, true, 'Subscription resumed');
    }

    /**
     * @param $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function subscribeRequestValidator($data)
    {
        return Validator::make($data, [
            'plan' => 'required|string|max:255',
        ]);
    }
}
