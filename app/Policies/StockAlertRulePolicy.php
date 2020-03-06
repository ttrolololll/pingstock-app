<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockAlertRule;
use Illuminate\Auth\Access\HandlesAuthorization;
use Laravel\Cashier\Subscription;

class StockAlertRulePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create stock alert rules.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        $allowedCount = config('app.free_stock_alerts', 10);
        $subscription = Subscription::where('user_id', $user->id)->first();

        if ($subscription && $subscription->stock_alerts != $allowedCount) {
            $allowedCount = $subscription->stock_alerts;
        }

        $currentCount = StockAlertRule::where([
            ['user_id', $user->id],
            ['triggered_at', null],
        ])->count();

        if ($currentCount < $allowedCount) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can modify stock alert rule.
     *
     * @param User $user
     * @param StockAlertRule $stockAlertRule
     * @return bool
     */
    public function modify(User $user, StockAlertRule $stockAlertRule)
    {
        return $user->id == $stockAlertRule->user_id;
    }
}
