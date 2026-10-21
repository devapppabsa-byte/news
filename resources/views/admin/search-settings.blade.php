<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Search provider credentials') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'search-settings-saved')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Credentials saved.') }}</div>
            @endif

            @if (session('status') === 'credential-removed')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Credential removed.') }}</div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="text-sm text-gray-600">
                    {{ __('Stored credentials are encrypted at rest. Existing values are never displayed; leave a field blank to keep the current value.') }}
                </p>

                <form method="POST" action="{{ route('admin.search.settings.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    @foreach ($credentialFields as $provider => $fields)
                        <fieldset class="border-t pt-4">
                            <legend class="font-semibold text-gray-900">{{ \App\Support\CredentialKeys::providerLabel($provider) }}</legend>

                            @foreach ($fields as $name => $label)
                                @php($status = $credentialStatus[$provider][$name] ?? 'missing')
                                <div class="mt-3">
                                    <x-input-label for="{{ $provider }}_{{ $name }}" :value="__($label)" />
                                    <div class="flex items-center gap-2">
                                        <x-text-input id="{{ $provider }}_{{ $name }}"
                                            name="credentials[{{ $provider }}][{{ $name }}]"
                                            type="password" autocomplete="off" class="mt-1 block w-full"
                                            placeholder="{{ $status === 'missing' ? __('Not configured') : __('•••••• configured') }}" />

                                        @if ($status === 'stored')
                                            <span class="mt-1 whitespace-nowrap text-xs text-green-600">{{ __('stored') }}</span>
                                            <a href="{{ route('admin.credentials.confirm', ['scope' => $scope, 'provider' => $provider, 'name' => $name]) }}"
                                                class="mt-1 whitespace-nowrap text-xs font-medium text-red-600 hover:text-red-800">
                                                {{ __('Remove') }}
                                            </a>
                                        @elseif ($status === 'environment')
                                            <span class="mt-1 whitespace-nowrap text-xs text-indigo-600">{{ __('environment') }}</span>
                                        @else
                                            <span class="mt-1 whitespace-nowrap text-xs text-gray-400">{{ __('missing') }}</span>
                                        @endif
                                    </div>
                                    <x-input-error :messages="$errors->get('credentials.'.$provider.'.'.$name)" class="mt-2" />
                                </div>
                            @endforeach
                        </fieldset>
                    @endforeach

                    <div>
                        <x-primary-button>{{ __('Save credentials') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
