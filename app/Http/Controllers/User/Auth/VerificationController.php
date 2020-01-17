<?php

namespace App\Http\Controllers\User\Auth;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class VerificationController extends Controller
{
    /**
     * @param Request $request
     * @param $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request, $code)
    {
        $user = User::where('email_verification_code', $code)->first();

        if (! $user) {
            return JsonResponseHelper::response(404, false, 'Invalid verfication code');
        }

        $user->email_verification_code = null;
        $user->email_verified_at = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();

        return JsonResponseHelper::response(200, true, 'Email verified');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, 'Invalid email');
        }

        $user = User::where('email', $request->post('email'))->first();

        if (! $user) {
            return JsonResponseHelper::response(404, false, 'Email not found');
        }

        if (! empty($user->email_verified_at)) {
            return JsonResponseHelper::response(400, false, 'Already verified');
        }

        $user->email_verification_code = Uuid::uuid4()->toString();
        $user->save();

        // queued mailable
        Mail::send(new EmailVerificationMail($user));

        return JsonResponseHelper::response(200, true, 'Verification email resent');
    }
}
