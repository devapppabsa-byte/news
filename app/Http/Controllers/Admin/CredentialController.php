<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Search\CredentialRepository;
use App\Support\CredentialKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CredentialController extends Controller
{
    public function __construct(
        protected CredentialRepository $credentials,
        protected AuditLogger $audit,
    ) {}

    /**
     * Show the confirmation page before removing a stored credential.
     */
    public function confirm(string $scope, string $provider, string $name): View
    {
        $this->ensureKnown($scope, $provider, $name);

        $fallback = config(CredentialKeys::configKey($provider, $name));
        $status = $this->credentials->status($provider, $name, $fallback);

        return view('admin.credentials.confirm', [
            'scope' => $scope,
            'provider' => $provider,
            'name' => $name,
            'label' => CredentialKeys::label($scope, $provider, $name),
            'providerLabel' => CredentialKeys::providerLabel($provider),
            'status' => $status,
            'hasEnvironmentFallback' => filled($fallback),
            'shared' => CredentialKeys::isShared($provider, $name),
        ]);
    }

    /**
     * Remove the stored credential.
     */
    public function destroy(string $scope, string $provider, string $name): RedirectResponse
    {
        $this->ensureKnown($scope, $provider, $name);

        $this->credentials->forget($provider, $name);

        $this->audit->record('credential.removed', null, [
            'scope' => $scope,
            'provider' => $provider,
            'name' => $name,
        ]);

        return redirect()
            ->route(CredentialKeys::routeName($scope))
            ->with('status', 'credential-removed');
    }

    private function ensureKnown(string $scope, string $provider, string $name): void
    {
        abort_unless(CredentialKeys::isKnown($scope, $provider, $name), 404);
    }
}
