<?php

namespace App\Http\Controllers\User\Watchlist;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\User;
use App\Models\Watchlist;
use App\Models\WatchlistItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WatchlistController extends Controller {

    public function listItems(Request $request)
    {
        $user = auth()->user();
        $items = WatchlistItem::with([
            'stock',
            'stockAlertRules' => function ($query) {
                $query->whereNull('triggered_at');
            }
        ])
            ->where('user_id', $user->id)
            ->get();
        return JsonResponseHelper::ok('', [], $items);
    }

    public function addItem(Request $request)
    {
        $data = $request->post();
        $validator = $this->validator($data);

        if ($validator->fails()) {
            return JsonResponseHelper::badRequest('Field validation failed', $validator->errors()->toArray());
        }

        /** @var User $user */
        $user = auth()->user();
        $watchlist = $this->initWatchlist($user);
        $stock = Stock::where('symbol', $data['stock_symbol'])->first();

        $item = new WatchlistItem();
        $item->user_id = $user->id;
        $item->watchlist_id = $watchlist->id;
        $item->alert_email = $user->email;
        $item->alert_telegram = $user->telegram_id;
        $item->stock_symbol = $stock->symbol;
        $item->exchange_symbol = $stock->exchange_symbol;
        $item->source = $stock->source;
        $item->circuit_breakers = json_encode(WatchlistItem::$defaultCircuitBreakers);

        if (!empty($data['reference_target'])) {
            $item->reference_target = $data['reference_target'];
        }

        try {
            $item->save();
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062){
                return JsonResponseHelper::badRequest('Already in watchlist');
            }
            Log::error($e->getMessage(), $e->getTrace());
            return JsonResponseHelper::internal('Unable to save watchlist item');
        }

        return JsonResponseHelper::ok('Saved to watchlist');
    }

    public function removeItem(Request $request)
    {
        $itemID = $request->post('item');

        if (empty($itemID)) {
            return JsonResponseHelper::badRequest('Item field must not be empty');
        }

        $user = auth()->user();
        $watchlist = $this->initWatchlist($user);
        $item = WatchlistItem::where([
            ['id', $itemID],
            ['user_id', $user->id],
            ['watchlist_id', $watchlist->id],
        ])->first();

        if (!$item) {
            return JsonResponseHelper::notFound('Item not found');
        }

        try {
            $item->delete();
        } catch (QueryException $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return JsonResponseHelper::internal('Unable to delete watchlist item');
        }

        return JsonResponseHelper::ok('Deleted from watchlist');
    }

    public function updateCircuitBreakers(Request $request, $itemID)
    {
        $cbs = $request->post('cbs');

        if (empty($cbs)) {
            return JsonResponseHelper::badRequest('Field cbs must not be empty');
        }

        try {
            $cbs = json_decode($cbs, true);
        } catch (\Exception $e) {
            return JsonResponseHelper::badRequest('Field cbs is not a valid JSON string');
        }

        if (!is_array($cbs)) {
            return JsonResponseHelper::badRequest('Field cbs is not an array        ');
        }

        $validCbs = $this->validateCircuitBreaker($cbs);

        if (!$validCbs) {
            return JsonResponseHelper::badRequest('Field cbs JSON fields invalid');
        }

        $item = WatchlistItem::where([
            ['id', $itemID],
            ['user_id', auth()->user()->id],
        ])->first();

        if (!$item) {
            return JsonResponseHelper::notFound('Watchlist item not found');
        }

        $itemCbs = json_decode($item->circuit_breakers, true);

        foreach ($validCbs as $key => $cb) {
            $cb['mute_till'] = $itemCbs[$key]['mute_till'];
            $cb['last_triggered'] = $itemCbs[$key]['last_triggered'];
            $itemCbs[$key] = $cb;
        }

        $item->circuit_breakers = $itemCbs;
        $item->save();

        return JsonResponseHelper::ok('Watchlist item circuit breakers updated');
    }

    public function changeCircuitBreakerState(Request $request, $itemID)
    {
        $data = $request->post();
        $validator = Validator::make($data, [
            'active' => 'required|bool',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::badRequest('', $validator->errors()->toArray());
        }

        $item = WatchlistItem::where([
            ['id', $itemID],
            ['user_id', auth()->user()->id],
        ])->first();

        if (!$item) {
            return JsonResponseHelper::notFound('Watchlist item not found');
        }

        $cbs = json_decode($item->circuit_breakers, true);

        foreach ($cbs as $key => $cb) {
            $cbs[$key]['is_active'] = $data['active'];
        }

        $item->circuit_breakers = json_encode($cbs);
        $item->save();

        return JsonResponseHelper::ok();
    }

    public function muteCircuitBreaker(Request $request, $itemID)
    {
        $duration = $request->post('duration');

        if (empty($duration)) {
            return JsonResponseHelper::badRequest('Please specify duration');
        }

        $user = auth()->user();
        $item = WatchlistItem::with('stock')->where([
            ['id', $itemID],
            ['user_id', $user->id],
        ])->first();

        if (!$item) {
            return JsonResponseHelper::notFound('Watchlist item not found');
        }

        $muteTill = now($item->stock->timezone);

        switch ($duration) {
            case 'week':
                $muteTill = $muteTill->nextWeekday()->startOfWeek();
                break;
            default:
                $duration = intval($duration);
                if (!$duration) {
                    return JsonResponseHelper::badRequest('Duration field is neither a valid word nor a number');
                }
                $muteTill = $muteTill->addMinutes($duration);
        }

        $cbs = json_decode($item->circuit_breakers, true);

        foreach ($cbs as $key => $cb) {
            $cbs[$key]['mute_till'] = $muteTill;
        }

        $item->circuit_breakers = json_encode($cbs);
        $item->save();

        return JsonResponseHelper::ok('Circuit breaker muted');
    }

    /**
     * Fetch watchlist or create if not exist
     *
     * @param User $user
     * @return Watchlist
     */
    protected function initWatchlist(User $user)
    {
        $watchlist = Watchlist::where('user_id', $user->id)->first();

        if (! $watchlist) {
            $watchlist = new Watchlist();
            $watchlist->user_id = $user->id;
            $watchlist->save();
        }

        return $watchlist;
    }

    /**
     * @param array $cbs
     * @return array|bool
     */
    protected function validateCircuitBreaker(array $cbs)
    {
        $validated = false;

        foreach ($cbs as $key => $cb) {
            if (!isset(WatchlistItem::$defaultCircuitBreakers[$key])) {
                continue;
            }
            if (!isset($cb['threshold']) || $cb['threshold'] <= 0) {
                continue;
            }
            if (!isset($cb['is_active'])) {
                continue;
            }
            $validated[$key]['threshold'] = WatchlistItem::$defaultCircuitBreakers[$key]['threshold'];
            $validated[$key]['is_active'] = $cb['is_active'];
//            $validated[$key]['mute_till'] = $cb['mute_till'] ?? null;
//            $validated[$key]['last_triggered'] = $cb['last_triggered'] ?? null;
        }

        return $validated;
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'stock_symbol' => 'required|exists:stocks,symbol',
            'reference_target' => 'required|numeric',
        ]);
    }

}
