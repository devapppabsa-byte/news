<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Email deliveries') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    @php
        $badge = fn (\App\Enums\DeliveryStatus $status) => match ($status) {
            \App\Enums\DeliveryStatus::Sent => 'bg-green-100 text-green-700',
            \App\Enums\DeliveryStatus::Failed => 'bg-red-100 text-red-700',
            \App\Enums\DeliveryStatus::Skipped => 'bg-yellow-100 text-yellow-800',
            default => 'bg-indigo-100 text-indigo-700',
        };
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.monitoring.tabs')

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('admin.monitoring.deliveries') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Recipient')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full"
                            :value="$filters['q']" placeholder="{{ __('Email address') }}" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status"
                            class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected($filters['status'] === $option->value)>{{ __($option->label()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('admin.monitoring.deliveries') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Recipient') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Topic') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Mode') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Items') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Attempts') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Queued') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Finished') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($deliveries as $delivery)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $delivery->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $delivery->monitoredTopic?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ __($delivery->mode->label()) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $badge($delivery->status) }}">
                                        {{ __($delivery->status->label()) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $delivery->items_count }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $delivery->attempts }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $delivery->queued_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $delivery->sent_at?->format('Y-m-d H:i') ?? $delivery->failed_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($delivery->error_message)
                                        <span class="text-red-600" title="{{ $delivery->error_message }}">
                                            {{ \Illuminate\Support\Str::limit($delivery->error_message, 80) }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-gray-500">{{ __('No deliveries found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $deliveries->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
