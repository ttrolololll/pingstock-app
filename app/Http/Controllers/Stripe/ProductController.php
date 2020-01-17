<?php

namespace App\Http\Controllers\Stripe;

use App\Helpers\JsonResponseHelper;
use Stripe\Collection;
use Stripe\Plan;
use Stripe\Product;

class ProductController extends StripeController
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function products()
    {
        $products = Product::all([
            'type' => 'service',
            'active' => true,
            'limit' => 100
        ]);

        $plans = Plan::all([
            'active' => true,
            'limit' => 100
        ]);

        $productsWithPlans = $this->mapPlansToProduct($plans, $products);

//        $transformed = [];
//
//        foreach ($products as $product) {
//            $p = $this->productTransformer($product);
//
//            if (! empty($p)) {
//                $transformed[] = $p;
//            }
//        }

        return JsonResponseHelper::response(200, true, '', [], $productsWithPlans);
    }

    /**
     * @param Collection $plans
     * @param Collection $products
     * @return array
     */
    protected function mapPlansToProduct($plans, $products)
    {
        $plansCollection = collect($plans['data']);
        $productsWithPlans = [];

        foreach ($products as $product) {
            $product['plans'] = $plansCollection->filter(function ($value, $key) use ($product) {
                return $value['product'] == $product['id'];
            })->all();
            $productsWithPlans[] = $product;
        }

        return $productsWithPlans;
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function productTransformer(Product $product)
    {
        if (isset($product['metadata']['stock_alerts_per_mth'])) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'stock_alerts_per_mth' => (int) $product['metadata']['stock_alerts_per_mth']
            ];
        }

        return [];
    }
}
