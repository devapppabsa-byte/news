<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('News') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('news-items.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="$filters['q']" placeholder="{{ __('Title or source') }}" />
                    </div>
                    <div>
                        <x-input-label for="topic" :value="__('Topic')" />
                        <select id="topic" name="topic" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($topics as $topic)
                                <option value="{{ $topic->id }}" @selected($filters['topic'] == $topic->id)>{{ $topic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter" :value="__('View')" />
                        <select id="filter" name="filter" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="" @selected($filters['filter'] === '')>{{ __('All') }}</option>
                            <option value="unread" @selected($filters['filter'] === 'unread')>{{ __('Unread') }}</option>
                            <option value="saved" @selected($filters['filter'] === 'saved')>{{ __('Saved') }}</option>
                            <option value="irrelevant" @selected($filters['filter'] === 'irrelevant')>{{ __('Irrelevant') }}</option>
                        </select>
                    </div>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('news-items.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg divide-y divide-gray-100">
                @forelse ($news as $item)
                    <article class="p-4 sm:p-6 space-y-2">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <a href="{{ route('news-items.show', $item) }}" class="text-base font-semibold text-gray-900 hover:underline">{{ $item->title }}</a>
                                <p class="text-xs text-gray-500">
                                    {{ $item->source_domain ?? __('Unknown source') }} • {{ $item->created_at?->diffForHumans() }}
                                    @if ($item->topic) • {{ $item->topic->name }} @endif
                                    @if ($item->ai_relevance !== null) • {{ __('Relevance') }} {{ $item->ai_relevance }}% @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if (! $item->is_read)<span class="px-2 py-0.5 text-xs rounded bg-indigo-100 text-indigo-700">{{ __('New') }}</span>@endif
                                @if ($item->is_saved)<span class="px-2 py-0.5 text-xs rounded bg-emerald-100 text-emerald-700">{{ __('Saved') }}</span>@endif
                            </div>
                        </div>
                        @if ($item->ai_summary)
                            <p class="text-sm text-gray-600">{{ $item->ai_summary }}</p>
                        @elseif ($item->body)
                            <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::limit(strip_tags($item->body), 280) }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <form method="POST" action="{{ route('news-items.read', $item) }}">@csrf<button class="px-2 py-1 rounded border {{ $item->is_read ? 'border-gray-300 text-gray-600' : 'border-indigo-300 text-indigo-700' }}">{{ $item->is_read ? __('Mark as unread') : __('Mark as read') }}</button></form>
                            <form method="POST" action="{{ route('news-items.save', $item) }}">@csrf<button class="px-2 py-1 rounded border {{ $item->is_saved ? 'border-emerald-300 text-emerald-700' : 'border-gray-300 text-gray-600' }}">{{ $item->is_saved ? __('Unsave') : __('Save') }}</button></form>
                            <form method="POST" action="{{ route('news-items.irrelevant', $item) }}">@csrf<button class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ $item->is_irrelevant ? __('Mark relevant') : __('Mark irrelevant') }}</button></form>
                            @if ($item->url)<a href="{{ $item->url }}" target="_blank" rel="noopener" class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ __('Open source') }}</a>@endif
                            <a href="{{ route('news-items.show', $item) }}" class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ __('Details') }}</a>
                        </div>
                    </article>
                @empty
                    <div class="p-6 text-center text-gray-500">{{ __('No news found.') }}</div>
                @endforelse
            </div>
            <div>{{ $news->links() }}</div>
        </div>
    </div>
</x-app-layout>
