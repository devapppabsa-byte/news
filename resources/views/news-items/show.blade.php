<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('News detail') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-xl font-semibold text-gray-900">{{ $newsItem->title }}</h1>
                    <div class="flex items-center gap-2 text-xs">
                        @if ($newsItem->is_read)<span class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ __('Read') }}</span>@endif
                        @if ($newsItem->is_saved)<span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">{{ __('Saved') }}</span>@endif
                        @if ($newsItem->is_irrelevant)<span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">{{ __('Irrelevant') }}</span>@endif
                    </div>
                </div>
                <p class="text-sm text-gray-500">
                    {{ $newsItem->source_domain ?? __('Unknown source') }}
                    @if ($newsItem->topic) • {{ $newsItem->topic->name }} @endif
                    @if ($newsItem->collected_at) • {{ $newsItem->collected_at->diffForHumans() }} @endif
                    @if ($newsItem->ai_relevance !== null) • {{ __('AI relevance') }} {{ $newsItem->ai_relevance }}% @endif
                </p>

                @if ($newsItem->ai_summary)
                    <div class="p-4 bg-gray-50 rounded-md">
                        <p class="text-sm font-medium text-gray-700">{{ __('AI summary') }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $newsItem->ai_summary }}</p>
                    </div>
                @endif

                @if ($newsItem->body)
                    <div class="prose max-w-none text-sm text-gray-800">
                        {!! nl2br(e($newsItem->body)) !!}
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <form method="POST" action="{{ route('news-items.read', $newsItem) }}">@csrf<button class="px-2 py-1 rounded border {{ $newsItem->is_read ? 'border-gray-300 text-gray-600' : 'border-indigo-300 text-indigo-700' }}">{{ $newsItem->is_read ? __('Mark as unread') : __('Mark as read') }}</button></form>
                    <form method="POST" action="{{ route('news-items.save', $newsItem) }}">@csrf<button class="px-2 py-1 rounded border {{ $newsItem->is_saved ? 'border-emerald-300 text-emerald-700' : 'border-gray-300 text-gray-600' }}">{{ $newsItem->is_saved ? __('Unsave') : __('Save') }}</button></form>
                    <form method="POST" action="{{ route('news-items.irrelevant', $newsItem) }}">@csrf<button class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ $newsItem->is_irrelevant ? __('Mark relevant') : __('Mark irrelevant') }}</button></form>
                    @if ($newsItem->url)<a href="{{ $newsItem->url }}" target="_blank" rel="noopener" class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ __('Open original source') }}</a>@endif
                    <a href="{{ route('news-items.index') }}" class="px-2 py-1 rounded border border-gray-300 text-gray-600">{{ __('Back to news') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
