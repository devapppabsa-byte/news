<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Error history') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    @include('admin.monitoring.tabs')

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('admin.monitoring.errors') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="$search" placeholder="{{ __('Topic, email or error message') }}" />
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('admin.monitoring.errors') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('Failed search runs') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Topic') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('User') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Trigger') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($failedRuns as $run)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $run->monitoredTopic?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->monitoredTopic?->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->trigger->value }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-red-600">{{ $run->error_message }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">{{ __('No failed runs.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-2">{{ $failedRuns->links() }}</div>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('Failed email deliveries') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Recipient') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Topic') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Mode') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($failedDeliveries as $delivery)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $delivery->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $delivery->monitoredTopic?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $delivery->mode->value }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $delivery->failed_at?->format('Y-m-d H:i') ?? $delivery->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-red-600">{{ $delivery->error_message }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">{{ __('No failed deliveries.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-2">{{ $failedDeliveries->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
