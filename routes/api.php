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
        Route::post('/register', 'User\Auth\RegisterController@register');
        Route::get('/verify/email/{hash}', 'User\Auth\VerificationController@verify');
        Route::post('/verify/email/resend', 'User\Auth\VerificationController@resend');
//        Route::post('/login', 'User\Auth\AuthController@login');
//        Route::post('/forgotpassword', 'User\Auth\AuthController@processForgotPasswordRequest');
//        Route::post('/forgotpassword/reset', 'User\Auth\AuthController@processForgotPasswordReset');
    });

    // Subscriptions
    Route::group(['prefix' => 'subscriptions'], function () {
        Route::get('/subscribe', 'Subscription\SubscriptionController@subscribe');
    });
});
