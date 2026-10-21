<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('AI provider configuration') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'ai-settings-saved')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('AI settings saved.') }}</div>
            @endif

            @if (session('status') === 'credential-removed')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Credential removed.') }}</div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="text-sm text-gray-600">
                    {{ __('The active configuration drives every AI analysis. API keys are managed by the platform administrator, stored encrypted at rest, and never displayed again.') }}
                </p>

                <form method="POST" action="{{ route('admin.ai.settings.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <fieldset class="border-t pt-4">
                        <legend class="font-semibold text-gray-900">{{ __('Provider') }}</legend>

                        <div class="mt-3">
                            <x-input-label for="provider" :value="__('Provider')" />
                            <select id="provider" name="provider"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider }}" @selected(old('provider', $config->provider) === $provider)>
                                        {{ ucfirst($provider) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('provider')" class="mt-2" />
                        </div>

                        <div class="mt-3">
                            <x-input-label for="model" :value="__('Model')" />
                            <x-text-input id="model" name="model" type="text" class="mt-1 block w-full"
                                :value="old('model', $config->model)" />
                            <x-input-error :messages="$errors->get('model')" class="mt-2" />
                        </div>

                        <div class="mt-3">
                            <x-input-label for="search_mode" :value="__('Search method')" />
                            <select id="search_mode" name="search_mode"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($searchModes as $mode)
                                    <option value="{{ $mode->value }}" @selected(old('search_mode', $config->searchMode->value) === $mode->value)>
                                        {{ ucfirst($mode->value) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('External uses the search providers from the previous phase; Integrated uses the model web tool when supported.') }}
                            </p>
                            <x-input-error :messages="$errors->get('search_mode')" class="mt-2" />
                        </div>
                    </fieldset>

                    <fieldset class="border-t pt-4">
                        <legend class="font-semibold text-gray-900">{{ __('Limits') }}</legend>

                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="max_tokens" :value="__('Max tokens')" />
                                <x-text-input id="max_tokens" name="max_tokens" type="number" min="64" max="32000"
                                    class="mt-1 block w-full" :value="old('max_tokens', $config->maxTokens)" />
                                <x-input-error :messages="$errors->get('max_tokens')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="timeout" :value="__('Timeout (seconds)')" />
                                <x-text-input id="timeout" name="timeout" type="number" min="5" max="300"
                                    class="mt-1 block w-full" :value="old('timeout', $config->timeout)" />
                                <x-input-error :messages="$errors->get('timeout')" class="mt-2" />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-t pt-4">
                        <legend class="font-semibold text-gray-900">{{ __('Fallback') }}</legend>

                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="fallback_provider" :value="__('Fallback provider')" />
                                <select id="fallback_provider" name="fallback_provider"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($providers as $provider)
                                        <option value="{{ $provider }}" @selected(old('fallback_provider', $config->fallbackProvider) === $provider)>
                                            {{ ucfirst($provider) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('fallback_provider')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="fallback_model" :value="__('Fallback model')" />
                                <x-text-input id="fallback_model" name="fallback_model" type="text"
                                    class="mt-1 block w-full" :value="old('fallback_model', $config->fallbackModel)" />
                                <x-input-error :messages="$errors->get('fallback_model')" class="mt-2" />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-t pt-4">
                        <legend class="font-semibold text-gray-900">{{ __('API keys') }}</legend>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Existing values are never displayed; leave a field blank to keep the current value.') }}
                        </p>

                        @foreach ($credentialFields as $provider => $fields)
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
                        @endforeach
                    </fieldset>

                    <div>
                        <x-primary-button>{{ __('Save AI settings') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
