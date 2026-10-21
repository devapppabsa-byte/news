<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ route('news-items.index') }}" class="text-indigo-600 hover:underline">{{ __('Explore news') }}</a>
                <a href="{{ route('topics.index') }}" class="text-indigo-600 hover:underline">{{ __('My topics') }}</a>
            </div>
        </div>
    </x-slot>

    @php
        $formatDate = fn ($d) => $d ? $d->diffForHumans() : null;
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 bg-white shadow sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Unread') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($unreadCount) }}</p>
                    <a href="{{ route('news-items.index', ['filter' => 'unread']) }}" class="mt-2 inline-block text-sm text-indigo-600 hover:underline">{{ __('View') }}</a>
                </div>
                <div class="p-4 bg-white shadow sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Saved') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($savedCount) }}</p>
                    <a href="{{ route('news-items.index', ['filter' => 'saved']) }}" class="mt-2 inline-block text-sm text-indigo-600 hover:underline">{{ __('View') }}</a>
                </div>
                <div class="p-4 bg-white shadow sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Active topics') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($activeTopics) }} / {{ number_format($topicsCount) }}</p>
                    <a href="{{ route('topics.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:underline">{{ __('Manage') }}</a>
                </div>
                <div class="p-4 bg-white shadow sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Search history') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($recentRuns->count()) }}</p>
                    <a href="{{ route('search-history.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:underline">{{ __('View all') }}</a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Latest news') }}</h3>
                        <a href="{{ route('news-items.index') }}" class="text-sm text-indigo-600 hover:underline">{{ __('Explore all') }}</a>
                    </div>

                    <div class="bg-white shadow sm:rounded-lg divide-y divide-gray-100">
                        @forelse ($latestNews as $item)
                            <article class="p-4 sm:p-6 space-y-2" x-data="{}">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <a href="{{ route('news-items.show', $item) }}" class="text-base font-semibold text-gray-900 hover:underline">
                                            {{ $item->title }}
                                        </a>
                                        <p class="text-xs text-gray-500">
                                            {{ $item->source_domain ?? __('Unknown source') }} • {{ $item->created_at?->diffForHumans() }}
                                            @if ($item->topic)
                                                • {{ $item->topic->name }}
                                            @endif
                                        </p>
                                    </div>
                                    @if (! $item->is_read)
                                        <span class="inline-flex px-2 py-0.5 text-xs rounded bg-indigo-100 text-indigo-700">{{ __('New') }}</span>
                                    @endif
                                </div>

                                @if ($item->ai_summary)
                                    <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($item->ai_summary, 220) }}</p>
                                @elseif ($item->body)
                                    <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::limit(strip_tags($item->body), 220) }}</p>
                                @endif

                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <form method="POST" action="{{ route('news-items.read', $item) }}">
                                        @csrf
                                        <button class="px-2 py-1 rounded border {{ $item->is_read ? 'border-gray-300 text-gray-600' : 'border-indigo-300 text-indigo-700' }}">
                                            {{ $item->is_read ? __('Mark as unread') : __('Mark as read') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('news-items.save', $item) }}">
                                        @csrf
                                        <button class="px-2 py-1 rounded border {{ $item->is_saved ? 'border-emerald-300 text-emerald-700' : 'border-gray-300 text-gray-600' }}">
                                            {{ $item->is_saved ? __('Unsave') : __('Save') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('news-items.irrelevant', $item) }}">
                                        @csrf
                                        <button class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ __('Mark irrelevant') }}</button>
                                    </form>
                                    @if ($item->url)
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener" class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ __('Open source') }}</a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="p-6 text-center text-gray-500">
                                {{ __('No news yet. Run a search or wait for the next scheduled analysis.') }}
                                <div class="mt-2">
                                    <a href="{{ route('topics.index') }}" class="text-indigo-600 hover:underline">{{ __('Go to topics') }}</a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('Upcoming analyses') }}</h3>
                            <a href="{{ route('topics.index') }}" class="text-xs text-indigo-600 hover:underline">{{ __('Manage') }}</a>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @forelse ($upcoming as $topic)
                                <li class="px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-900">{{ $topic->name }}</p>
                                        <p class="text-xs text-gray-500">{{ __('Next: :time', ['time' => $topic->next_run_at?->diffForHumans()]) }}</p>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded {{ $topic->status->value === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ __($topic->status->value) }}
                                    </span>
                                </li>
                            @empty
                                <li class="px-4 py-4 text-sm text-gray-500">{{ __('No scheduled analyses.') }}</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('Recent runs') }}</h3>
                            <a href="{{ route('search-history.index') }}" class="text-xs text-indigo-600 hover:underline">{{ __('History') }}</a>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @forelse ($recentRuns as $run)
                                <li class="px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-gray-900">{{ $run->monitoredTopic?->name }}</p>
                                        <span class="text-xs px-2 py-0.5 rounded {{ $run->status->value === 'success' ? 'bg-green-100 text-green-700' : ($run->status->value === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                            {{ __($run->status->value) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ $run->results_count }} {{ __('results') }} • {{ $run->created_at?->diffForHumans() }}</p>
                                </li>
                            @empty
                                <li class="px-4 py-4 text-sm text-gray-500">{{ __('No runs yet.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
