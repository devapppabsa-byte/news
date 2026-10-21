<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $user->name }}
            </h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Back to users') }}</a>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'user-suspended')
                <div class="p-4 bg-amber-50 text-amber-700 rounded-md">{{ __('User suspended.') }}</div>
            @elseif (session('status') === 'user-reactivated')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('User reactivated.') }}</div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase text-gray-400">{{ __('Email') }}</dt>
                        <dd class="text-gray-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-400">{{ __('Role') }}</dt>
                        <dd class="text-gray-900">{{ __($user->role->label()) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-400">{{ __('Status') }}</dt>
                        <dd class="text-gray-900">
                            {{ __($user->status->label()) }}
                            @if ($user->suspended_at)
                                <span class="text-xs text-gray-500">· {{ $user->suspended_at->format('Y-m-d H:i') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-400">{{ __('Verification') }}</dt>
                        <dd class="text-gray-900">
                            {{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i') : __('Not verified') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-400">{{ __('Registered') }}</dt>
                        <dd class="text-gray-900">{{ $user->created_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>

                <div class="grid grid-cols-2 gap-4 border-t pt-4 sm:grid-cols-4">
                    <div>
                        <p class="text-2xl font-semibold text-gray-900">{{ $user->monitored_topics_count }}</p>
                        <p class="text-xs text-gray-500">{{ __('Topics') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-gray-900">{{ $user->news_items_count }}</p>
                        <p class="text-xs text-gray-500">{{ __('Novedades') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-gray-900">{{ $user->notification_deliveries_count }}</p>
                        <p class="text-xs text-gray-500">{{ __('Notifications') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-gray-900">{{ $user->push_subscriptions_count }}</p>
                        <p class="text-xs text-gray-500">{{ __('Push devices') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t pt-6" x-data="{ confirm: false }">
                    @if ($user->isSuspended())
                        <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                            @csrf
                            <x-primary-button>{{ __('Reactivate account') }}</x-primary-button>
                        </form>
                    @else
                        <template x-if="! confirm">
                            <button type="button" @click="confirm = true"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                {{ __('Suspend account') }}
                            </button>
                        </template>

                        <template x-if="confirm">
                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="flex items-center gap-3">
                                @csrf
                                <span class="text-sm text-gray-600">{{ __('Suspend this account? It becomes read-only.') }}</span>
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                    {{ __('Confirm') }}
                                </button>
                                <button type="button" @click="confirm = false" class="text-sm text-gray-500 hover:underline">{{ __('Cancel') }}</button>
                            </form>
                        </template>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
