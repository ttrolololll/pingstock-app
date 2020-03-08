<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram {operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commands for managing Telegram Bot';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->{$this->argument('operation')}();
        } catch (\Exception $e) {
            $this->info($e->getMessage());
        }
    }

    protected function getWebhookInfo()
    {
        $telegram = new TelegramService(config('services.telegram-bot-api.token'));
        $resp = $telegram->getWebhookInfo();
        $this->info($resp->getBody());
    }

    protected function setWebhook()
    {
        $telegram = new TelegramService(config('services.telegram-bot-api.token'));
        $resp = $telegram->setWebhook(config('app.url') . '/webhooks/telegram/' . config('services.telegram-bot-api.token') . '/message');
        $this->info($resp->getBody());
    }
}
