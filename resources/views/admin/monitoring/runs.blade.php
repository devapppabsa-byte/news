<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Search runs') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    @php
        $badge = fn (\App\Enums\RunStatus $status) => match ($status) {
            \App\Enums\RunStatus::Success => 'bg-green-100 text-green-700',
            \App\Enums\RunStatus::Failed => 'bg-red-100 text-red-700',
            \App\Enums\RunStatus::Running => 'bg-indigo-100 text-indigo-700',
            default => 'bg-yellow-100 text-yellow-800',
        };
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.monitoring.tabs')

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('admin.monitoring.runs') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Topic')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full"
                            :value="$filters['q']" placeholder="{{ __('Topic name') }}" />
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

                    <div>
                        <x-input-label for="trigger" :value="__('Trigger')" />
                        <select id="trigger" name="trigger"
                            class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($triggers as $option)
                                <option value="{{ $option->value }}" @selected($filters['trigger'] === $option->value)>{{ __($option->label()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('admin.monitoring.runs') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Topic') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('User') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Trigger') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Results') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Duration') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Cost') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Started') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($runs as $run)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $run->monitoredTopic?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->monitoredTopic?->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ __($run->trigger->label()) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $badge($run->status) }}">
                                        {{ __($run->status->label()) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $run->results_count }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    {{ $run->duration_ms === null ? '—' : number_format($run->duration_ms / 1000, 1).'s' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format((float) $run->estimated_cost, 6) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->started_at?->format('Y-m-d H:i') ?? $run->queued_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($run->error_message)
                                        <span class="text-red-600" title="{{ $run->error_message }}">
                                            {{ \Illuminate\Support\Str::limit($run->error_message, 80) }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-gray-500">{{ __('No runs found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $runs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
