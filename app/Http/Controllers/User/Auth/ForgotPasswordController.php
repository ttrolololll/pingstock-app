<?php

namespace App\Http\Controllers\User\Auth;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPasswordRequest(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, '', $validator->errors());
        }

        $broker = $this->broker();
        $user = $broker->getUser(['email' => $request->post('email')]);

        if (! $user) {
            return JsonResponseHelper::response(200, true, 'If the email exists, you will receive a password reset link in your email');
        }

        $tokenRepo = $broker->getRepository();

        if (method_exists($tokenRepo, 'recentlyCreatedToken') &&
            $tokenRepo->recentlyCreatedToken($user)) {
            return JsonResponseHelper::response(429, false, 'Too many request');
        }

        $resetToken = $tokenRepo->create($user);

        Mail::send(new ForgotPasswordMail($resetToken, $user));

        return response()->json([
            "success" => true,
            "message" => 'If the email exists, you will receive a password reset link in your email'
        ], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPasswordReset(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'token' => 'required',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',

        ]);

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, 'Validation errors', $validator->errors());
        }

        if (! User::validatePassword($request->post('password'))) {
            return JsonResponseHelper::response(400, false, 'Password must consists of at least one uppercase, lowercase, numeric and special character');
        }

        $broker = $this->broker();
        $respStr = $broker->reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        });

        if ($respStr != PasswordBroker::PASSWORD_RESET) {
            return JsonResponseHelper::response(400, false, 'Reset token is invalid or expired');
        }

        return JsonResponseHelper::response(200, true, 'Password has been reset successfully');
    }

    /**
     * @return PasswordBroker
     */
    protected function broker()
    {
        return Password::broker();
    }
}
