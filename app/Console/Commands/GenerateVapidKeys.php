<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    /**
     * @var string
     */
    protected $signature = 'webpush:vapid';

    /**
     * @var string
     */
    protected $description = 'Generate a VAPID key pair for web push notifications';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->newLine();
        $this->components->info('Add these values to your .env file:');
        $this->newLine();

        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:you@example.com');
        $this->newLine();

        $this->components->warn('Keep the private key secret. Never commit it to version control.');

        return self::SUCCESS;
    }
}
