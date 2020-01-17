<?php

namespace App\Http\Controllers\User;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller {

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return JsonResponseHelper::response(200, true, '', [], auth()->user());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validator = $this->validator($data = $request->post());

        if ($validator->fails()) {
            return JsonResponseHelper::response(400, false, '', $validator->errors());
        }

        $user = auth()->user();
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];

        if ($user->email != $data['email']) {
            $otherUsers = User::where('email', $data['email'])->get();

            if (! $otherUsers->isEmpty()) {
                return JsonResponseHelper::response(400, false, 'Email has been taken, please choose another email');
            }

            $user->email = $data['email'];
        }

        $user->save();

        return JsonResponseHelper::response();
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);
    }

}