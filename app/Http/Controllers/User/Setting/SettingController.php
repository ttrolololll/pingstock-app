<?php

namespace App\Http\Controllers\User\Setting;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ServiceVerification;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function getSettings(Request $request)
    {
        $settings = [];

        $user = auth()->user();

        if ($user->telegram_id) {
            $settings['notifications']['telegram']['is_set'] = true;
            return JsonResponseHelper::ok('', [], $settings);
        }

        $settings['notifications']['telegram']['is_set'] = false;
        $sv = ServiceVerification::where('user_id', $user->id)->get();

        foreach ($sv as $row) {
            $settings['notifications'][$row->service] = [
                'verification_token' => $row->token,
                'verification_token_expiry' => $row->expires_at->unix(),
            ];
        }

        return JsonResponseHelper::ok('', [], $settings);
    }
}
