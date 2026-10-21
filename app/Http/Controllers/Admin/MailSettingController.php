<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MailSettingRequest;
use App\Mail\MailTest;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\MailFromResolver;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class MailSettingController extends Controller
{
    public function __construct(
        protected SettingsRepository $settings,
        protected MailFromResolver $from,
        protected AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        $values = collect(SettingKeys::mailKeys())
            ->mapWithKeys(fn (string $key): array => [$key => $this->settings->get($key)])
            ->all();

        return view('admin.mail-settings', [
            'values' => $values,
            'transport' => (string) config('mail.default'),
        ]);
    }

    public function update(MailSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach (SettingKeys::mailKeys() as $key) {
            if (Arr::has($validated, $key)) {
                $this->settings->set($key, Arr::get($validated, $key));
            }
        }

        $this->audit->record('mail.settings.updated', null, [
            'keys' => $this->audit->changedKeys($validated),
        ]);

        return redirect()
            ->route('admin.mail.settings')
            ->with('status', 'mail-settings-saved');
    }

    /**
     * Send a test message with the current configuration.
     */
    public function test(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            Mail::to($validated['email'])->send(new MailTest($this->from->resolve()));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.mail.settings')
                ->withErrors(['email' => __('The test email could not be sent: :message', [
                    'message' => $exception->getMessage(),
                ])]);
        }

        return redirect()
            ->route('admin.mail.settings')
            ->with('status', 'mail-test-sent');
    }
}
