<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $topic->name }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('topics.edit', $topic) }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <form method="POST" action="{{ route('topics.run', $topic) }}">
                    @csrf
                    <x-primary-button>{{ __('Run search now') }}</x-primary-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @foreach (['topic-updated' => __('Topic updated.'), 'topic-paused' => __('Topic paused.'), 'topic-resumed' => __('Topic resumed.'), 'topic-queued' => __('Search queued. Results will appear when the worker finishes.')] as $key => $message)
                @if (session('status') === $key)
                    <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ $message }}</div>
                @endif
            @endforeach

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <div class="text-xs uppercase text-gray-500">{{ __('Status') }}</div>
                        <div class="mt-1 font-medium">{{ $topic->isActive() ? __('Active') : __('Paused') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500">{{ __('Frequency') }}</div>
                        <div class="mt-1 font-medium">
                            {{ ucfirst($topic->frequency->value) }}
                            @if ($topic->frequency === \App\Enums\SearchFrequency::Custom)
                                ({{ $topic->custom_interval_minutes }} min)
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500">{{ __('Last run') }}</div>
                        <div class="mt-1 font-medium">{{ $topic->last_run_at?->diffForHumans() ?? __('Never') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500">{{ __('Next run') }}</div>
                        <div class="mt-1 font-medium">{{ $topic->next_run_at?->diffForHumans() ?? __('Manual') }}</div>
                    </div>
                </div>

                @if ($topic->description)
                    <p class="mt-4 text-sm text-gray-700">{{ $topic->description }}</p>
                @endif

                <div class="mt-6 flex items-center gap-2">
                    @if ($topic->isActive())
                        <form method="POST" action="{{ route('topics.pause', $topic) }}">
                            @csrf
                            @method('PATCH')
                            <x-secondary-button>{{ __('Pause') }}</x-secondary-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('topics.resume', $topic) }}">
                            @csrf
                            @method('PATCH')
                            <x-secondary-button>{{ __('Resume') }}</x-secondary-button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('topics.destroy', $topic) }}"
                        onsubmit="return confirm('{{ __('Delete this topic?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Delete') }}</x-danger-button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                    <h3 class="font-semibold text-gray-900">{{ __('Include keywords') }}</h3>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @forelse ($topic->includedKeywords as $keyword)
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs rounded">{{ $keyword->value }}</span>
                        @empty
                            <span class="text-sm text-gray-500">{{ __('None') }}</span>
                        @endforelse
                    </div>

                    <h3 class="mt-4 font-semibold text-gray-900">{{ __('Exclude keywords') }}</h3>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @forelse ($topic->excludedKeywords as $keyword)
                            <span class="px-2 py-1 bg-red-50 text-red-700 text-xs rounded">{{ $keyword->value }}</span>
                        @empty
                            <span class="text-sm text-gray-500">{{ __('None') }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                    <h3 class="font-semibold text-gray-900">{{ __('Preferred domains') }}</h3>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @forelse ($topic->preferredSources as $source)
                            <span class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded">{{ $source->domain }}</span>
                        @empty
                            <span class="text-sm text-gray-500">{{ __('None') }}</span>
                        @endforelse
                    </div>

                    <h3 class="mt-4 font-semibold text-gray-900">{{ __('Excluded domains') }}</h3>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @forelse ($topic->excludedSources as $source)
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">{{ $source->domain }}</span>
                        @empty
                            <span class="text-sm text-gray-500">{{ __('None') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ __('Results') }}</h3>
                    <div class="flex flex-wrap gap-1 text-xs">
                        @foreach (['' => __('All'), 'new' => __('New'), 'saved' => __('Saved'), 'unread' => __('Unread'), 'irrelevant' => __('Irrelevant')] as $key => $label)
                            <a href="{{ route('topics.show', [$topic, 'filter' => $key ?: null]) }}"
                                class="px-2 py-1 rounded {{ $filter === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse ($results as $result)
                        @php($entry = $result->newsEntry)
                        <div class="border-b pb-4 last:border-b-0 last:pb-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <a href="{{ $result->url }}" target="_blank" rel="noopener noreferrer"
                                        class="font-medium text-indigo-700 hover:underline">
                                        {{ $result->title }}
                                    </a>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <span class="px-2 py-0.5 rounded {{ match ($result->novedad_status) {
                                            \App\Enums\NewsItemStatus::New => 'bg-green-50 text-green-700',
                                            \App\Enums\NewsItemStatus::Existing => 'bg-gray-100 text-gray-700',
                                            \App\Enums\NewsItemStatus::Irrelevant => 'bg-red-50 text-red-700',
                                        } }}">{{ __(ucfirst($result->novedad_status->value)) }}</span>
                                        @if ($result->is_saved)
                                            <span class="px-2 py-0.5 rounded bg-yellow-50 text-yellow-700">{{ __('Saved') }}</span>
                                        @endif
                                        @if ($entry?->source_available === false)
                                            <span class="px-2 py-0.5 rounded bg-orange-50 text-orange-700">{{ __('Source unavailable') }}</span>
                                        @endif
                                        <span>{{ $result->source_domain }}</span>
                                        @if ($result->published_at)
                                            <span>&middot; {{ __('Published') }} {{ $result->published_at->format('Y-m-d') }}</span>
                                        @endif
                                        @if ($entry?->discovered_at)
                                            <span>&middot; {{ __('Found') }} {{ $entry->discovered_at->format('Y-m-d H:i') }}</span>
                                        @endif
                                        @if ($entry?->relevance_score !== null)
                                            <span>&middot; {{ __('Relevance') }} {{ $entry->relevance_score }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if ($entry?->summary ?? $result->body)
                                <p class="mt-2 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($entry?->summary ?? $result->body, 300) }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <form method="POST" action="{{ route('news-items.read', $result) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">
                                        {{ $result->is_read ? __('Mark unread') : __('Mark read') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('news-items.save', $result) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">
                                        {{ $result->is_saved ? __('Unsave') : __('Save') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('news-items.irrelevant', $result) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">
                                        {{ $result->is_irrelevant ? __('Mark relevant') : __('Mark irrelevant') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No results yet. Run a search to collect results.') }}</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $results->links() }}</div>
            </div>

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <h3 class="font-semibold text-gray-900">{{ __('Run history') }}</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500">
                                <th class="py-2 pr-4">{{ __('Date') }}</th>
                                <th class="py-2 pr-4">{{ __('Trigger') }}</th>
                                <th class="py-2 pr-4">{{ __('Status') }}</th>
                                <th class="py-2 pr-4">{{ __('Results') }}</th>
                                <th class="py-2 pr-4">{{ __('Duration') }}</th>
                                <th class="py-2 pr-4">{{ __('Attempts') }}</th>
                                <th class="py-2 pr-4">{{ __('Est. cost') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($runs as $run)
                                <tr class="border-t">
                                    <td class="py-2 pr-4">{{ $run->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="py-2 pr-4">{{ ucfirst($run->trigger->value) }}</td>
                                    <td class="py-2 pr-4">
                                        <span class="px-2 py-0.5 rounded text-xs {{ match ($run->status) {
                                            \App\Enums\RunStatus::Success => 'bg-green-50 text-green-700',
                                            \App\Enums\RunStatus::Failed => 'bg-red-50 text-red-700',
                                            \App\Enums\RunStatus::Running => 'bg-blue-50 text-blue-700',
                                            \App\Enums\RunStatus::Queued, \App\Enums\RunStatus::Pending => 'bg-gray-100 text-gray-700',
                                        } }}">{{ ucfirst($run->status->value) }}</span>
                                    </td>
                                    <td class="py-2 pr-4">{{ $run->results_count }}</td>
                                    <td class="py-2 pr-4">{{ $run->duration_ms !== null ? number_format($run->duration_ms / 1000, 2).'s' : '—' }}</td>
                                    <td class="py-2 pr-4">{{ $run->attempts }}</td>
                                    <td class="py-2 pr-4">{{ number_format((float) $run->estimated_cost, 4) }}</td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="7" class="py-4 text-center text-gray-500">{{ __('No runs yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $runs->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
