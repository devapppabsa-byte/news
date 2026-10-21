<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchSettingRequest;
use App\Services\Audit\AuditLogger;
use App\Services\Search\CredentialRepository;
use App\Support\CredentialKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SearchSettingController extends Controller
{
    public function __construct(
        protected CredentialRepository $credentials,
        protected AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        return view('admin.search-settings', [
            'scope' => CredentialKeys::SCOPE_SEARCH,
            'credentialFields' => CredentialKeys::fields(CredentialKeys::SCOPE_SEARCH),
            'credentialStatus' => $this->credentials->statusesFor(CredentialKeys::SCOPE_SEARCH),
        ]);
    }

    public function update(SearchSettingRequest $request): RedirectResponse
    {
        foreach (CredentialKeys::fields(CredentialKeys::SCOPE_SEARCH) as $provider => $names) {
            foreach (array_keys($names) as $name) {
                $value = $request->input("credentials.{$provider}.{$name}");

                if (filled($value)) {
                    $this->credentials->put($provider, $name, trim((string) $value));
                }
            }
        }

        $this->audit->record('search.settings.updated');

        return redirect()
            ->route('admin.search.settings')
            ->with('status', 'search-settings-saved');
    }
}
