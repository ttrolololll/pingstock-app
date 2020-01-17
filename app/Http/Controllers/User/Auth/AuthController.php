<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->all(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    public function me(Request $request)
    {
        return response()->json(auth()->user());
    }

    public function resetPassword(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'current_password' => 'required|string|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "errors" => $validator->errors()
            ], 400);
        }

        $user = auth()->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                "success" => false,
                "message" => 'Incorrect password'
            ], 400);
        }

        $user->password = Hash::make($data['password']);

        if ($user->is_first_login) {
            $user->is_first_login = 0;
        }

        $user->save();

        return response()->json([
            "success" => true,
        ]);
    }

    public function processForgotPasswordRequest(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "errors" => $validator->errors()
            ], 400);
        }

        $broker = $this->broker();
        $user = $broker->getUser($request->only('email'));

        if (is_null($user)) {
            return response()->json([
                "success" => true,
                "message" => 'If the email exists, you will receive a password reset link in your email'
            ], 200);
        }

        $tokenRepo = $broker->getRepository();

        if (method_exists($tokenRepo, 'recentlyCreatedToken') &&
            $tokenRepo->recentlyCreatedToken($user)) {
            return response()->json([
                "success" => false,
                "message" => 'Too many request'
            ], 429);
        }

        $resetToken = $tokenRepo->create($user);

        Mail::to($user->email)->send(new ForgotPasswordMail($resetToken, $user));

        return response()->json([
            "success" => true,
            "message" => 'If the email exists, you will receive a password reset link in your email'
        ], 200);
    }

    public function processForgotPasswordReset(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:255|confirmed',

        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "errors" => $validator->errors()
            ], 400);
        }

        $broker = $this->broker();
        $respStr = $broker->reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        });

        if ($respStr != PasswordBroker::PASSWORD_RESET) {
            return response()->json([
                "success" => false,
                "message" => 'Reset token is invalid or expired'
            ], 400);
        }

        return response()->json([
            "success" => true,
            "message" => 'Password has been reset successfully'
        ], 200);
    }

    protected function broker()
    {
        return Password::broker();
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
