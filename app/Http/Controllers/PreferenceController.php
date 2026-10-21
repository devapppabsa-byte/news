<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePreferenceRequest;
use App\Models\Preference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class PreferenceController extends Controller
{
    /**
     * Persist the user's notification preferences.
     */
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

        return Redirect::route('profile.edit')->with('status', 'preferences-updated');
    }
}
