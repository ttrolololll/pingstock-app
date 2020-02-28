<?php

namespace App\Http\Controllers\User\StockAlert;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockAlertRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockAlertController extends Controller
{

    /**
     * Fulltext search for stocks based on symbols and names
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchStock(Request $request)
    {
        return Stock::search($request->get('s'))->get();
    }

    /**
     * Get user's un-triggered Stock Alert Rules
     *
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        $user = auth()->user();
        $triggeredCond = 0;
        $triggered = $request->get('triggered');

        if (! empty($triggered)) {
            $triggeredCond = intval($triggered);
        }

        $where = [
            ['user_id', $user->id],
            ['triggered', $triggeredCond]
        ];

        $alerts = StockAlertRule::where($where)->get();

        return $alerts;
    }

    /**
     * Creates new Stock Alert Rule
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newStockAlert(Request $request)
    {
        $data = $request->post();
        $validator = $this->stockAlertRuleValidator($data);

        // validate params
        if ($validator->fails()) {
            return JsonResponseHelper::badRequest('Bad request', $validator->errors());
        }
        if (! in_array(strtolower($data['operator']), StockAlertRule::$operators)) {
            return JsonResponseHelper::badRequest('Invalid operator param');
        }

        // retrieve stock data
        $stock = Stock::with('exchange')->where('symbol', $data['stock_symbol'])->first();

        if (! $stock) {
            return JsonResponseHelper::badRequest('Invalid stock_symbol param');
        }

        $user = auth()->user();

        // check permission
        if ($user->cant('create', StockAlertRule::class)) {
            return JsonResponseHelper::forbidden('Limit exceeded');
        }

        // save
        $alertRule = new StockAlertRule();
        $alertRule->user_id = $user->id;
        $alertRule->alert_email = $user->email;
        $alertRule->stock_symbol = $data['stock_symbol'];
        $alertRule->exchange_symbol = $stock->exchange->symbol;
        $alertRule->target = $data['target'];
        $alertRule->target_type = 'price';
        $alertRule->operator = $data['operator'];
        $alertRule->source = $stock->source;

        $alertRule->save();

        return JsonResponseHelper::ok('Alert rule created successfully');
    }

    /**
     * Updates Stock Alert Rule
     *
     * @param Request $request
     * @param $stockAlertID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $stockAlertID)
    {
        $data = $request->post();
        $validator = $this->stockAlertRuleValidator($data);

        // validate params
        if ($validator->fails()) {
            return JsonResponseHelper::badRequest('Bad request', $validator->errors());
        }
        if (! in_array(strtolower($data['operator']), StockAlertRule::$operators)) {
            return JsonResponseHelper::badRequest('Invalid operator param');
        }

        $alertRule = StockAlertRule::find($stockAlertID);

        if (! $alertRule) {
            return JsonResponseHelper::notFound('Invalid stock alert');
        }

        if ($alertRule->triggered) {
            return JsonResponseHelper::badRequest('Stock alert rule already triggered');
        }

        $user = auth()->user();

        // check permission
        if ($user->cant('modify', $alertRule)) {
            return JsonResponseHelper::forbidden('No permission to perform action');
        }

        // update is stock symbol unchanged
        if ($alertRule->stock_symbol == $data['stock_symbol']) {
            $alertRule->target = $data['target'];
            $alertRule->operator = $data['operator'];
            $alertRule->save();

            return JsonResponseHelper::ok('Stock alerted rule updated');
        }

        // retrieve stock data since symbol is changed
        $stock = Stock::with('exchange')->where('symbol', $data['stock_symbol'])->first();

        if (! $stock) {
            return JsonResponseHelper::badRequest('Invalid stock_symbol param');
        }

        // save
        $alertRule->alert_email = $user->email;
        $alertRule->stock_symbol = $data['stock_symbol'];
        $alertRule->exchange_symbol = $stock->exchange->symbol;
        $alertRule->target = $data['target'];
        $alertRule->target_type = 'price';
        $alertRule->operator = $data['operator'];
        $alertRule->source = $stock->source;
        $alertRule->save();

        return JsonResponseHelper::ok('Stock alerted rule updated');
    }

    /**
     * Delete user's Stock Alert Rule
     *
     * @param Request $request
     * @param $stockAlertID
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $stockAlertID)
    {
        $alertRule = StockAlertRule::find($stockAlertID);

        if (! $alertRule) {
            return JsonResponseHelper::notFound('Invalid stock alert rule');
        }

        if ($alertRule->triggered) {
            return JsonResponseHelper::badRequest('Stock alert rule already triggered');
        }

        $user = auth()->user();

        // check permission
        if ($user->cant('modify', $alertRule)) {
            return JsonResponseHelper::forbidden('No permission to perform action');
        }

        $alertRule->delete();

        return JsonResponseHelper::ok('Stock Alert Rule deleted');
    }

    /**
     * Returns Stock Alert Rule input params. validator
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function stockAlertRuleValidator(array $data)
    {
        return Validator::make($data, [
            'stock_symbol' => 'required|max:255',
            'target' => 'required|numeric',
            'operator' => 'required|max:255',
        ]);
    }

}
