<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Audit log') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    @php
        $label = fn (string $action) => match ($action) {
            'user.suspended' => __('User suspended'),
            'user.reactivated' => __('User reactivated'),
            'settings.updated' => __('Global settings updated'),
            'settings.reset' => __('Global settings reset'),
            'mail.settings.updated' => __('Email settings updated'),
            'search.settings.updated' => __('Search credentials updated'),
            'ai.settings.updated' => __('AI settings updated'),
            'credential.removed' => __('Credential removed'),
            default => str_replace('.', ' ', $action),
        };
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('admin.audit') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Actor or subject')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full"
                            :value="$filters['q']" placeholder="{{ __('Name, email or subject id') }}" />
                    </div>

                    <div>
                        <x-input-label for="action" :value="__('Action')" />
                        <select id="action" name="action"
                            class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($actions as $option)
                                <option value="{{ $option }}" @selected($filters['action'] === $option)>{{ __($label($option)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="from" :value="__('From')" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from']" />
                    </div>

                    <div>
                        <x-input-label for="to" :value="__('To')" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to']" />
                    </div>

                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('admin.audit') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Actor') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Action') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Subject') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('IP') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Details') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-900">
                                    @if ($log->actor)
                                        {{ $log->actor->name }}
                                        <span class="block text-xs text-gray-500">{{ $log->actor->email }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('System') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900">{{ __($label($log->action)) }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($log->subject)
                                        {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $log->ip_address ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($log->metadata)
                                        <pre class="text-xs whitespace-pre-wrap">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('No audit events found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
