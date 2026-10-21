<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Monitored topics') }}
            </h2>
            <a href="{{ route('topics.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('New topic') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'topic-deleted')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Topic deleted.') }}</div>
            @endif

            @forelse ($topics as $topic)
                <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <a href="{{ route('topics.show', $topic) }}" class="text-lg font-semibold text-gray-900 hover:underline">
                                {{ $topic->name }}
                            </a>
                            <div class="mt-1 text-sm text-gray-500">
                                {{ ucfirst($topic->frequency->value) }}
                                &middot; {{ $topic->language }}@if ($topic->country) - {{ $topic->country }} @endif
                                &middot; {{ $topic->isActive() ? __('Active') : __('Paused') }}
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                {{ trans_choice(':count keyword|:count keywords', $topic->keywords_count, ['count' => $topic->keywords_count]) }}
                                &middot; {{ trans_choice(':count source|:count sources', $topic->sources_count, ['count' => $topic->sources_count]) }}
                                &middot; {{ trans_choice(':count run|:count runs', $topic->runs_count, ['count' => $topic->runs_count]) }}
                            </div>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            @if ($topic->next_run_at)
                                {{ __('Next run') }}<br>{{ $topic->next_run_at->diffForHumans() }}
                            @else
                                {{ __('Manual') }}
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 bg-white shadow sm:rounded-lg text-center text-gray-500">
                    {{ __('You have no monitored topics yet.') }}
                </div>
            @endforelse

            <div>
                {{ $topics->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
