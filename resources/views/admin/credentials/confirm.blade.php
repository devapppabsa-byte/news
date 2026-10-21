<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Remove credential') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-6">
                <div class="p-4 bg-red-50 text-red-700 rounded-md text-sm">
                    {{ __('This removes the stored value from the encrypted credential store. This action cannot be undone.') }}
                </div>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Provider') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $providerLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Field') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $label }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Current source') }}</dt>
                        <dd class="font-medium text-gray-900">
                            @if ($status === 'stored')
                                {{ __('Stored in the panel') }}
                            @elseif ($status === 'environment')
                                {{ __('Environment configuration') }}
                            @else
                                {{ __('Not configured') }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($hasEnvironmentFallback)
                    <p class="text-sm text-gray-600">
                        {{ __('An environment value is also configured. After removing the stored value, the environment value will be used again.') }}
                    </p>
                @endif

                @if ($shared)
                    <p class="text-sm text-gray-600">
                        {{ __('This credential is shared by other providers, so removing it also disables it there.') }}
                    </p>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route(\App\Support\CredentialKeys::routeName($scope)) }}"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900">
                        {{ __('Cancel') }}
                    </a>

                    <form method="POST"
                        action="{{ route('admin.credentials.destroy', ['scope' => $scope, 'provider' => $provider, 'name' => $name]) }}">
                        @csrf
                        <x-danger-button type="submit">{{ __('Remove credential') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
