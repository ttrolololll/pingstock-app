<?php

namespace App\Http\Controllers\Webhook;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ServiceVerification;
use App\Models\StockAlertRule;
use App\Models\User;
use App\Notifications\StockPriceAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Telegram\TelegramChannel;

class TelegramController extends Controller {

    public static $commands = ['/start', '/link', '/unlink'];

    /**
     * Handles incoming Telegram webhook requests
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleMessage(Request $request)
    {
        $chatID = '';
        $chatType = '';
        $content = '';
        $entities = [];

        try {
            if ($request->message['chat']['type'] != 'private') {
                return JsonResponseHelper::ok();
            }
            if (empty($request->message['entities'])) {
                return JsonResponseHelper::ok();
            }

            $chatID = $request->message['chat']['id'];
            $content = $request->message['text'];
            $entities = $request->message['entities'];
        } catch (\Exception $e) {
            Log::error('[TelegramController.handleMessage] unable to parse message: ' .$e->getMessage(), $e->getTrace());
            return JsonResponseHelper::ok();
        }

        $cmd = $this->extractCommand($content, $entities);

        if (!$this->isCommandValid($cmd)) {
            return JsonResponseHelper::ok();
        }

        try {
            $cmdMethod = 'handleCommand' . ucfirst(trim($cmd, '/'));
            $this->{$cmdMethod}($chatID, $content, $entities);
        } catch (\Exception $e) {
            Log::error('[TelegramController.handleMessage] command handler does not exists: ' .$e->getMessage(), $e->getTrace());
            return JsonResponseHelper::ok();
        }

        return JsonResponseHelper::ok();
    }

    /**
     * Handles /start
     *
     * @param $chatID
     * @param $content
     * @param $entities
     */
    protected function handleCommandStart($chatID, $content, $entities)
    {
        $msg = '';
        $user = User::where('telegram_id', $chatID)->first();

        if ($user) {
            $msg = 'Howdy! Your Telegram is already linked with your PingStock.io account. You will receive stock alerts here when your rules are triggered.';
        }

        Notification::route(TelegramChannel::class, $chatID)
            ->notify(new StockPriceAlert(null, $msg));
    }

    /**
     * Handles /link email token
     *
     * @param $chatID
     * @param $content
     * @param $entities
     * @throws \Exception
     */
    protected function handleCommandLink($chatID, $content, $entities)
    {
        $msgParts = explode(' ', $content);

        if (count($msgParts) !== 3) {
            Notification::route(TelegramChannel::class, $chatID)
                ->notify(new StockPriceAlert(null, 'Link command format must be: /link your-email your-token'));
            return;
        }

        $msg = 'Linking successful! You will receive stock alerts here when your rules are triggered.';
        $user = User::where('telegram_id', $chatID)->first();

        if ($user) {
            $msg = 'Howdy! Your Telegram is already linked with your PingStock.io account. You will receive stock alerts here when your rules are triggered.';
        }

        // token verification
        $sv = null;
        try {
            $sv = ServiceVerification::verifyToken('telegram', $msgParts[2], $msgParts[1]);
        } catch (\Exception $e) {
            Log::error('[TelegramController.handleCommandLink] ' . $e->getMessage());
            Notification::route(TelegramChannel::class, $chatID)
                ->notify(new StockPriceAlert(null, $e->getMessage()));
            return;
        }

        if (!$sv) {
            Notification::route(TelegramChannel::class, $chatID)
                ->notify(new StockPriceAlert(null, 'Unable to verify token'));
            return;
        }

        $user = User::where([
            ['id', '=', $sv->user_id],
            ['email', '=', $sv->email],
        ])->first();

        if (!$user) {
            Notification::route(TelegramChannel::class, $chatID)
                ->notify(new StockPriceAlert(null, 'Token verified but user invalid'));
            return;
        }

        $user->telegram_id = $chatID;
        $user->save();
        $sv->delete();

        StockAlertRule::where('user_id', $user->id)
            ->whereNull('triggered_at')
            ->update(['alert_telegram' => $chatID]);

        Notification::route(TelegramChannel::class, $chatID)
            ->notify(new StockPriceAlert(null, $msg));
    }

    /**
     * Handles /unlink email
     * @param $chatID
     * @param $content
     * @param $entities
     */
    protected function handleCommandUnlink($chatID, $content, $entities)
    {
        $msgParts = explode(' ', $content);

        if (count($msgParts) !== 2) {
            Notification::route(TelegramChannel::class, $chatID)
                ->notify(new StockPriceAlert(null, 'Unlink command format must be: /unlink your-email'));
            return;
        }
        if (!filter_var($msgParts[1], FILTER_VALIDATE_EMAIL)) {
            Notification::route(TelegramChannel::class, $chatID)
                ->notify(new StockPriceAlert(null, 'Field email is not a valid email address'));
            return;
        }

        $user = User::where([
            ['email', '=', $msgParts[1]],
            ['telegram_id', '=', $chatID],
        ])->first();

        if (!$user) {
            Notification::route(TelegramChannel::class, $chatID)
                ->notify(new StockPriceAlert(null, 'User not found'));
            return;
        }

        $user->telegram_id = null;
        $user->save();

        StockAlertRule::where('user_id', $user->id)
            ->whereNull('triggered_at')
            ->update(['alert_telegram' => null]);

        Notification::route(TelegramChannel::class, $chatID)
            ->notify(new StockPriceAlert(null, 'Unlink successful, sorry to see you go :('));
    }

    /**
     * Extract the first command found in given entities
     *
     * @param $msgText
     * @param array $entities
     * @return false|string
     */
    protected function extractCommand($msgText, array $entities)
    {
        $entity = null;

        foreach ($entities as $ent) {
            if ($ent['type'] == 'bot_command') {
                $entity = $ent;
                break;
            }
        }

        return substr($msgText, $entity['offset'], $entity['length']);
    }

    /**
     * Checks if command is allow
     *
     * @param $cmd
     * @return bool
     */
    protected function isCommandValid($cmd)
    {
        return in_array($cmd, self::$commands);
    }

}
