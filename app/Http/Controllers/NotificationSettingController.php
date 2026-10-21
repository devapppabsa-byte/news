<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePreferenceRequest;
use App\Models\Preference;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationSettingController extends Controller
{
    public function __construct(
        protected SettingsRepository $settings,
    ) {}

    public function edit(): View
    {
        $user = request()->user();
        $preference = Preference::query()->firstOrNew(['user_id' => $user->id]);

        return view('notifications.edit', [
            'preference' => $preference,
            'notificationsEnabled' => $this->settings->boolean(SettingKeys::NOTIFICATIONS_ENABLED)
                ?? (bool) config('notifications.enabled', true),
        ]);
    }

    public function update(UpdatePreferenceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $preference = Preference::query()
            ->where('user_id', $request->user()->id)
            ->first()
            ?? new Preference(['user_id' => $request->user()->id]);

        $preference->fill([
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'email_notifications' => $request->boolean('email_notifications'),
            'notification_mode' => $validated['notification_mode'],
            'push_notifications' => $request->boolean('push_notifications'),
            'push_private' => $request->boolean('push_private'),
            'digest_frequency' => $validated['digest_frequency'],
        ]);

        $preference->user_id = $request->user()->id;
        $preference->save();

        return redirect()->route('notifications.edit')->with('status', 'preferences-updated');
    }
}
