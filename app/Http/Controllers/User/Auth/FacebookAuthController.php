<?php

namespace App\Http\Controllers\User\Auth;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FacebookService;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FacebookAuthController extends Controller
{
    /**
     * Facebook authentication handler takes in signed request,
     * get access token from Facebook and either register or login before returning user JWT token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws FacebookSDKException
     */
    public function authWithFacebook(Request $request)
    {
        $sr = $request->post('signed_request');

        if (empty($sr)) {
            return JsonResponseHelper::badRequest('Signed request must not be empty');
        }

        // use Facebook SDK to obtain access token from signed request
        $fbSDKClient = new Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v6.0'
        ]);
        $fbHelper = $fbSDKClient->getJavaScriptHelper();
        $fbHelper->instantiateSignedRequest($sr);
        $fbAccessToken = null;

        try {
            $fbAccessToken = $fbHelper->getAccessToken();
        } catch (FacebookResponseException $e) {
            return JsonResponseHelper::badRequest('Facebook Graph error: ' . $e->getMessage());
        } catch (FacebookSDKException $e) {
            return JsonResponseHelper::internal('Facebook SDK error: ' . $e->getMessage());
        }

        if (!$fbAccessToken) {
            return JsonResponseHelper::internal('Facebook access token error');
        }

        // use our own FB client to make call and retrieve FB user info
        $fbUserResp = null;
        $fbUser = null;
        $fbService = new FacebookService();
        try {
            $fbUserResp = $fbService->me($fbAccessToken->getValue());
        } catch (\Exception $e) {
            return JsonResponseHelper::badRequest('Facebook access token invalid');
        }

        try {
            $fbUser = json_decode($fbUserResp->getBody(), true);
        } catch (\Exception $e) {
            return JsonResponseHelper::internal('Unable to parse Facebook JSON response');
        }

        /** @var User $user */
        $user = User::where('facebook_id', $fbUser['id'])->first();

        // if Facebook ID is found, user exists so login and return jwt
        if ($user) {
            $token = auth()->login($user);
            return $this->respondWithToken($token);
        }

        $user = User::where('email', $fbUser['email'])->first();

        // if email is not found, good to register user and return jwt
        if (!$user) {
            $user = $this->generateUser($fbUser['email'], $fbUser['name'], $fbUser['id']);
            $user->save();
            $token = auth()->login($user);
            return $this->respondWithToken($token);
        }

        //  else user can neither login nor register
        return JsonResponseHelper::badRequest('The email associated with your Facebook already has an account. Please login to the account using the email and your password and link to Facebook via the dashboard');
    }

    /**
     * Link user with Facebook
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function linkWithFacebook(Request $request)
    {
        $fbID = $request->post('facebook_id');

        if (empty($fbID)) {
            return JsonResponseHelper::badRequest('Empty Facebook ID');
        }

        $checkFBUserExists = User::where('facebook_id', $fbID)->first();
        if ($checkFBUserExists) {
            return JsonResponseHelper::badRequest('Your Facebook is already linked with another account');
        }

        $user = auth()->user();

        User::where('id', $user->id)->update(['facebook_id' => $fbID]);
        return JsonResponseHelper::ok();
    }

    /**
     * Unlink user with Facebook
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlinkWithFacebook(Request $request)
    {
        $user = auth()->user();

        User::where('id', $user->id)->update(['facebook_id' => null]);
        return JsonResponseHelper::ok();
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

    /**
     * Generates user using Facebook auth result
     *
     * @param $email
     * @param $name
     * @param $fbID
     * @return User
     */
    protected function generateUser($email, $name, $fbID)
    {
        $firstName = $name;
        $lastName = 'lastname';
        $nameParts = explode(' ', $name);

        if (count($nameParts) > 1) {
            $firstName = $nameParts[0];
            $lastName = $nameParts[1];
        }

        $user = new User();
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->email = $email;
        $user->email_verified_at = now();
        $user->password = Hash::make(Str::random(12));
        $user->facebook_id = $fbID;

        return $user;
    }
}
