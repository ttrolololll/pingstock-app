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
        });
    });

    // Stripe - Products
    Route::group(['prefix' => 'products'], function () {
        Route::get('/', 'Stripe\ProductController@products');
    });

    // Subscriptions
    Route::group(['prefix' => 'subscriptions'], function () {
        Route::get('/subscribe', 'Subscription\SubscriptionController@subscribe');
    });
});
