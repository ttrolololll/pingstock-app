<?php

namespace App\Http\Controllers\User\Auth;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->post();

        if (! $token = auth()->attempt($credentials)) {
            return JsonResponseHelper::response(401, false, 'Unauthorized');
        }

        if (empty(auth()->user()->email_verified_at)) {
            return JsonResponseHelper::response(403, false, 'User email unverified');
        }

        return $this->respondWithToken($token);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return JsonResponseHelper::response(200, true, 'Successfully logged out');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request) {
        $validator = Validator::make($request->post(), [
            'current_password' => 'required|string|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string|max:255',
        ]);

        $newPw = $request->post('password');

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, '', $validator->errors());
        }

        if (! User::validatePassword($newPw)) {
            return JsonResponseHelper::response(400, false, 'Password must consists of at least one uppercase, lowercase, numeric and special character');
        }

        $user = auth()->user();

        if (! Hash::check($request->post('current_password'), $user->password)) {
            return JsonResponseHelper::response(401, false, 'The email or password is invalid');
        }

        $user->password = Hash::make($newPw);
        $user->save();

        return JsonResponseHelper::response(200, true, 'Password reset successful');
    }

    /**
     * @param $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return JsonResponseHelper::response(200, true, 'Login success', [], [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'expires_at' => now()->addMinute(auth()->factory()->getTTL())->timestamp
        ]);
    }
}
