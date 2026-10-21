<?php

use App\Mail\MailTest;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Support\Facades\Mail;

test('an admin can update the sender and notification settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.mail.settings.update'), [
            'mail' => [
                'from_address' => 'no-reply@example.com',
                'from_name' => 'News Monitor',
            ],
            'notifications' => [
                'enabled' => '0',
                'min_relevance' => 40,
            ],
        ])
        ->assertRedirect(route('admin.mail.settings'))
        ->assertSessionHas('status', 'mail-settings-saved');

    $settings = app(SettingsRepository::class);

    expect($settings->get(SettingKeys::MAIL_FROM_ADDRESS))->toBe('no-reply@example.com')
        ->and($settings->get(SettingKeys::MAIL_FROM_NAME))->toBe('News Monitor')
        ->and($settings->boolean(SettingKeys::NOTIFICATIONS_ENABLED))->toBeFalse()
        ->and($settings->integer(SettingKeys::NOTIFICATIONS_MIN_RELEVANCE))->toBe(40);
});

test('mail settings are validated', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.mail.settings.update'), [
            'mail' => ['from_address' => 'not-an-email'],
            'notifications' => ['enabled' => 'maybe', 'min_relevance' => 200],
        ])
        ->assertSessionHasErrors(['mail.from_address', 'notifications.enabled', 'notifications.min_relevance']);
});

test('the test email uses the configured sender', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();

    app(SettingsRepository::class)->set(SettingKeys::MAIL_FROM_ADDRESS, 'panel@example.com');
    app(SettingsRepository::class)->set(SettingKeys::MAIL_FROM_NAME, 'Panel Sender');

    $this->actingAs($admin)
        ->post(route('admin.mail.test'), ['email' => 'recipient@example.com'])
        ->assertRedirect(route('admin.mail.settings'))
        ->assertSessionHas('status', 'mail-test-sent');

    Mail::assertSent(MailTest::class, fn (MailTest $mail): bool => $mail->hasTo('recipient@example.com')
        && $mail->hasFrom('panel@example.com', 'Panel Sender'));
});

test('a non admin cannot manage email settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.mail.settings'))->assertForbidden();
    $this->actingAs($user)->put(route('admin.mail.settings.update'), [])->assertForbidden();
    $this->actingAs($user)->post(route('admin.mail.test'), ['email' => 'x@example.com'])->assertForbidden();
});
