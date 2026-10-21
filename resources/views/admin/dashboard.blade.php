<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Admin') }}
            </h2>
            <span class="text-sm text-gray-500">{{ __('Last :days days', ['days' => $windowDays]) }}</span>
        </div>
    </x-slot>

    @include('admin.partials.nav')

    @php
        $cards = [
            [
                'label' => __('Users'),
                'value' => number_format($metrics['users_total']),
                'hint' => __('+:count new in the window', ['count' => number_format($metrics['users_new'])]),
            ],
            [
                'label' => __('Active topics'),
                'value' => number_format($metrics['topics_active']),
                'hint' => __(':count total', ['count' => number_format($metrics['topics_total'])]),
            ],
            [
                'label' => __('Search runs'),
                'value' => number_format($metrics['runs_total']),
                'hint' => $metrics['runs_success_rate'] === null
                    ? __('No runs in the window')
                    : __(':rate% success, :failed failed', ['rate' => $metrics['runs_success_rate'], 'failed' => number_format($metrics['runs_failed'])]),
            ],
            [
                'label' => __('Results collected'),
                'value' => number_format($metrics['results_total']),
                'hint' => __(':count new items stored', ['count' => number_format($metrics['news_items'])]),
            ],
            [
                'label' => __('Emails sent'),
                'value' => number_format($metrics['notifications_sent']),
                'hint' => __(':count failed', ['count' => number_format($metrics['notifications_failed'])]),
            ],
            [
                'label' => __('Estimated cost'),
                'value' => number_format($metrics['estimated_cost'], 4),
                'hint' => __(':tokens AI tokens', ['tokens' => number_format($metrics['ai_tokens'])]),
            ],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($cards as $card)
                    <div class="p-6 bg-white shadow sm:rounded-lg">
                        <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $card['hint'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700">{{ __('AI usage by provider') }}</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Provider') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Runs') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Tokens') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Share') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($aiUsage as $usage)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $usage->provider }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($usage->runs) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format((int) $usage->tokens) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">
                                        {{ $metrics['ai_tokens'] > 0 ? number_format((int) $usage->tokens / $metrics['ai_tokens'] * 100, 1).'%' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('No AI usage recorded.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700">{{ __('Cost by user') }}</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('User') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Runs') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Results') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Estimated cost') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($costByUser as $row)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.users.show', $row->id) }}" class="text-indigo-600 hover:underline">
                                            {{ $row->name }}
                                        </a>
                                        <span class="block text-xs text-gray-500">{{ $row->email }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row->runs) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format((int) $row->results) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format((float) $row->cost, 6) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('No activity in the window.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($metrics['average_duration_seconds'] !== null)
                <p class="text-sm text-gray-500">
                    {{ __('Average run duration: :seconds s', ['seconds' => $metrics['average_duration_seconds']]) }}
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
