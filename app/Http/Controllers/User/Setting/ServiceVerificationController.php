<?php

namespace App\Http\Controllers\User\Setting;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ServiceVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ServiceVerificationController extends Controller
{
    public function generateToken(Request $request)
    {
        $service = $request->post('service');

        if (!$service) {
            return JsonResponseHelper::badRequest('Field service must not be empty');
        }
        if (!ServiceVerification::isValidService($service)) {
            return JsonResponseHelper::badRequest('Unsupported service');
        }

        /** @var User $user */
        $user = auth()->user();

        if (User::isUserServiceLinked($user, $service)) {
            return JsonResponseHelper::badRequest('Already has a linked ' . ucfirst($service) . ' account');
        }

        $sv = ServiceVerification::updateOrCreate(
            ['user_id' => $user->id, 'service' => $service],
            [
                'user_id' => $user->id,
                'email' => $user->email,
                'service' => $service,
                'token' => Str::random(4),
                'expires_at' => Carbon::now()->addMinutes(2)
            ]
        );

        return JsonResponseHelper::ok('', [], [
            'token' => $sv->token,
            'expires_at' => $sv->expires_at->unix()
        ]);
    }

    public function unlink(Request $request)
    {
        $service = $request->post('service');

        if (!$service) {
            return JsonResponseHelper::badRequest('Field service must not be empty');
        }
        if (!ServiceVerification::isValidService($service)) {
            return JsonResponseHelper::badRequest('Unsupported service');
        }

        /** @var User $user */
        $user = auth()->user();

        if ($user->{$service . '_id'}) {
            $user->{$service . '_id'} = null;
            $user->save();
        }

        return JsonResponseHelper::ok(ucfirst($service) . ' unlinked from account successfully');
    }

//    public function verifyToken(Request $request)
//    {
//        $service = $request->post('service');
//        $token = $request->post('token');
//
//        if (!$service || !$token) {
//            return JsonResponseHelper::badRequest('Field service and token must not be empty');
//        }
//        if (!ServiceVerification::isValidService($service)) {
//            return JsonResponseHelper::badRequest('Unsupported service');
//        }
//
//        /** @var User $user */
//        $user = auth()->user();
//
//        if (User::isUserServiceLinked($user, $service)) {
//            return JsonResponseHelper::badRequest('Already has a linked ' . ucfirst($service) . ' account');
//        }
//
//        $sv = ServiceVerification::where([
//            ['user_id', '=', $user->id],
//            ['service', '=', $service],
//            ['token', '=', $token],
//        ])->first();
//
//        if (!$sv) {
//            return JsonResponseHelper::badRequest('Invalid token');
//        }
//        if ($sv->expires_at->lte(now())) {
//            return JsonResponseHelper::badRequest('Token expired');
//        }
//
//        return JsonResponseHelper::ok();
//    }
}
