<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        protected SettingsRepository $settings,
        protected AuditLogger $audit,
    ) {}

    /**
     * Show the global platform settings.
     */
    public function edit(): View
    {
        $values = collect(SettingKeys::globalKeys())
            ->mapWithKeys(fn (string $key): array => [$key => $this->settings->get($key)])
            ->all();

        return view('admin.settings', [
            'values' => $values,
        ]);
    }

    /**
     * Persist the global platform settings.
     */
    public function update(SettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach (SettingKeys::globalKeys() as $key) {
            if (! Arr::has($validated, $key)) {
                continue;
            }

            $this->settings->set($key, Arr::get($validated, $key));
        }

        $this->audit->record('settings.updated', null, [
            'keys' => $this->audit->changedKeys($validated),
        ]);

        return Redirect::route('admin.settings')
            ->with('status', 'settings-saved');
    }

    /**
     * Drop every stored value so the settings fall back to their defaults.
     */
    public function reset(): RedirectResponse
    {
        $this->settings->forgetAll();

        $this->audit->record('settings.reset');

        return Redirect::route('admin.settings')
            ->with('status', 'settings-reset');
    }
}
