<?php

namespace App\Http\Controllers\User\Auth;

use App\Helpers\JsonResponseHelper;
use App\Mail\EmailVerification;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class RegisterController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $data = $request->post();
        $validator = $this->validator($data);

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, '', $validator->errors());
        }

        if (! User::validatePassword($data['password'])) {
            return JsonResponseHelper::response(400, false, 'Password must consists of at least one uppercase, lowercase, numeric and special character');
        }

        $user = $this->create($data);

        // Send queued verification mailable
        Mail::send(new EmailVerification($user));

        event(new Registered($user));

        return JsonResponseHelper::response(201, true, 'User created successfully');
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);
    }

    /**
     * @param array $data
     * @return User
     */
    protected function create(array $data)
    {
        $user = new User();
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->email_verification_code = Uuid::uuid4()->toString();
        $user->password = Hash::make($data['password']);
        $user->save();

        return $user;
    }

    protected function sendVerificationEmail(User $user)
    {

    }
}
