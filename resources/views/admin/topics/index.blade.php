<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Topics and executions') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'topics-bulk-updated')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Bulk action applied.') }}</div>
            @endif

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('admin.topics.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$search" placeholder="{{ __('Topic name') }}" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                            <option value="paused" @selected($status === 'paused')>{{ __('Paused') }}</option>
                        </select>
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('admin.topics.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <form method="POST" action="{{ route('admin.topics.bulk') }}" class="space-y-4">
                @csrf
                <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg flex flex-wrap items-center gap-3">
                    <select name="action" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="">{{ __('Bulk action') }}</option>
                        <option value="pause">{{ __('Pause') }}</option>
                        <option value="resume">{{ __('Resume') }}</option>
                        <option value="run">{{ __('Run now') }}</option>
                    </select>
                    <x-primary-button>{{ __('Apply') }}</x-primary-button>
                </div>

                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3"></th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Topic') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('User') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Frequency') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Runs') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Last run') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Next run') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($topics as $topic)
                                <tr>
                                    <td class="px-4 py-3"><input type="checkbox" name="topics[]" value="{{ $topic->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"></td>
                                    <td class="px-4 py-3 text-gray-900">{{ $topic->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $topic->user?->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ __($topic->status->value) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $topic->frequency->value }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $topic->runs_count }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $topic->last_run_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $topic->next_run_at?->diffForHumans() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">{{ __('No topics found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <div>{{ $topics->links() }}</div>
        </div>
    </div>
</x-app-layout>
