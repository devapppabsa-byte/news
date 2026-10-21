<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Search history') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('search-history.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Topic')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="$filters['q']" placeholder="{{ __('Topic name') }}" />
                    </div>
                    <div>
                        <x-input-label for="topic" :value="__('Topic filter')" />
                        <select id="topic" name="topic" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($topics as $t)
                                <option value="{{ $t->id }}" @selected($filters['topic'] == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>{{ __($s->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('search-history.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Topic') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Trigger') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Results') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Duration') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Started') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($runs as $run)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $run->monitoredTopic?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->trigger->value }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->status->value }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $run->results_count }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $run->duration_ms ? number_format($run->duration_ms / 1000, 1).'s' : '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->started_at?->format('Y-m-d H:i') ?? $run->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $run->error_message ? \Illuminate\Support\Str::limit($run->error_message, 60) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">{{ __('No runs found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $runs->links() }}</div>
        </div>
    </div>
</x-app-layout>
