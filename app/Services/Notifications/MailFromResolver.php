<?php

namespace App\Services\Notifications;

use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;

/**
 * Resolves the outgoing "from" identity at send time so the panel can change it
 * without editing configuration or restarting workers.
 */
class MailFromResolver
{
    public function __construct(
        protected SettingsRepository $settings,
    ) {}

    /**
     * @return array{address: string, name: string}
     */
    public function resolve(): array
    {
        $address = trim((string) $this->settings->get(SettingKeys::MAIL_FROM_ADDRESS));
        $name = trim((string) $this->settings->get(SettingKeys::MAIL_FROM_NAME));

        if ($address === '') {
            $address = (string) config('mail.from.address');
        }

        if ($name === '') {
            $name = (string) config('mail.from.name');
        }

        return ['address' => $address, 'name' => $name];
    }
}
