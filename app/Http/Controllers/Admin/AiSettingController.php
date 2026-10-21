<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AISearchMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AiSettingRequest;
use App\Services\AI\AIConfigRepository;
use App\Services\Audit\AuditLogger;
use App\Services\Search\CredentialRepository;
use App\Support\CredentialKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiSettingController extends Controller
{
    public function __construct(
        protected AIConfigRepository $config,
        protected CredentialRepository $credentials,
        protected AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        return view('admin.ai-settings', [
            'config' => $this->config->current(),
            'scope' => CredentialKeys::SCOPE_AI,
            'credentialFields' => CredentialKeys::fields(CredentialKeys::SCOPE_AI),
            'credentialStatus' => $this->credentials->statusesFor(CredentialKeys::SCOPE_AI),
            'searchModes' => AISearchMode::cases(),
            'providers' => array_keys(CredentialKeys::fields(CredentialKeys::SCOPE_AI)),
        ]);
    }

    public function update(AiSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->config->put([
            'provider' => $validated['provider'],
            'model' => $validated['model'],
            'search_mode' => $validated['search_mode'],
            'parameters' => [],
            'max_tokens' => $validated['max_tokens'],
            'timeout' => $validated['timeout'],
            'fallback_provider' => $validated['fallback_provider'] ?? null,
            'fallback_model' => $validated['fallback_model'] ?? null,
        ]);

        foreach (CredentialKeys::fields(CredentialKeys::SCOPE_AI) as $provider => $names) {
            foreach (array_keys($names) as $name) {
                $value = $request->input("credentials.{$provider}.{$name}");

                if (filled($value)) {
                    $this->credentials->put($provider, $name, trim((string) $value));
                }
            }
        }

        $this->audit->record('ai.settings.updated', null, [
            'provider' => $validated['provider'],
            'model' => $validated['model'],
            'search_mode' => $validated['search_mode'],
        ]);

        return redirect()
            ->route('admin.ai.settings')
            ->with('status', 'ai-settings-saved');
    }
}
