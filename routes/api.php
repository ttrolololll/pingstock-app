<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1'], function () {
    Route::get('/health', 'HealthController@health');

    // Users
    Route::group(['prefix' => 'users'], function () {
        // Users - On-boarding
        Route::post('/register', 'User\Auth\RegisterController@register');
        Route::post('/verify/email/resend', 'User\Auth\VerificationController@resend');
        Route::get('/verify/email/{code}', 'User\Auth\VerificationController@verify')->name('verify.email');
        Route::post('/auth/pwresetreq', 'User\Auth\ForgotPasswordController@forgotPasswordRequest');
        Route::post('/auth/pwreset', 'User\Auth\ForgotPasswordController@forgotPasswordReset');
        Route::post('/auth/login', 'User\Auth\AuthController@login');

        Route::group(['middleware' => ['auth:jwt']], function () {
            // Users - Auth
            Route::post('/auth/logout', 'User\Auth\AuthController@logout');
            Route::post('/auth/tokens/refresh', 'User\Auth\AuthController@refresh');
            Route::post('/auth/pw', 'User\Auth\AuthController@resetPassword');
            // Users - Profile
            Route::get('/profile', 'User\ProfileController@me');
            Route::patch('/profile', 'User\ProfileController@update');
            // Users - Settings - Services
//            Route::get('/settings/services', 'User\SettingController@getStockAlertSettings');
//            Route::patch('/settings/services', 'User\SettingController@updateStockAlertSettings');
            Route::middleware('throttle:2,1')
                ->post('/settings/services/tokens', 'User\Setting\ServiceVerificationController@generateToken');
//            Route::post('/settings/services/tokens/verify', 'User\Setting\ServiceVerificationController@verifyToken');
            // Users - Payment
            Route::post('/payment/setup-intent', 'User\Payment\PaymentController@newSetupIntent');
            Route::get('/payment/cards', 'User\Payment\PaymentController@paymentMethods');
            Route::post('/payment/cards', 'User\Payment\PaymentController@addPaymentMethod');
            Route::delete('/payment/cards/all', 'User\Payment\PaymentController@deleteAllPaymentMethods');
            // Users - Subscription
            Route::get('/subscriptions', 'User\Subscription\SubscriptionController@currentSubscription');
            Route::post('/subscriptions', 'User\Subscription\SubscriptionController@subscribe');
            Route::post('/subscriptions/cancel', 'User\Subscription\SubscriptionController@cancelSubscription');
            Route::post('/subscriptions/resume', 'User\Subscription\SubscriptionController@resumeSubscription');
//            Route::post('/subscriptions/upgrade', 'User\Subscription\SubscriptionController@upgradeSubscription');
            // Users - Stock Alerts
            // [TODO] stock search endpoint should not be under stock alerts
            Route::get('/stocks/search', 'User\StockAlert\StockAlertController@searchStock');
            Route::get('/stock-alerts', 'User\StockAlert\StockAlertController@getList');
            Route::post('/stock-alerts', 'User\StockAlert\StockAlertController@newStockAlert');
            Route::patch('/stock-alerts/{stockAlertID}', 'User\StockAlert\StockAlertController@update');
            Route::delete('/stock-alerts/{stockAlertID}', 'User\StockAlert\StockAlertController@delete');
        });
    });

    // Stripe - Products
    Route::group(['prefix' => 'products'], function () {
        Route::get('/', 'Stripe\ProductController@products');
    });

    // Webhooks
    Route::group(['prefix' => 'webhooks'], function () {
        Route::post('/telegram/' . config('services.telegram-bot-api.token') . '/message', 'Webhook\TelegramController@handleMessage');
    });
});
