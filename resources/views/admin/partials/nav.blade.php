@php
    $items = [
        ['route' => 'admin.dashboard', 'label' => __('Dashboard')],
        ['route' => 'admin.users.index', 'label' => __('Users')],
        ['route' => 'admin.topics.index', 'label' => __('Topics & runs')],
        ['route' => 'admin.search.settings', 'label' => __('Search providers')],
        ['route' => 'admin.ai.settings', 'label' => __('AI providers')],
    ];

    if (\Illuminate\Support\Facades\Route::has('admin.settings')) {
        $items[] = ['route' => 'admin.settings', 'label' => __('Global settings')];
    }

    if (\Illuminate\Support\Facades\Route::has('admin.mail.settings')) {
        $items[] = ['route' => 'admin.mail.settings', 'label' => __('Email')];
    }

    if (\Illuminate\Support\Facades\Route::has('admin.monitoring.runs')) {
        $items[] = ['route' => 'admin.monitoring.runs', 'label' => __('Monitoring')];
    }

    if (\Illuminate\Support\Facades\Route::has('admin.audit')) {
        $items[] = ['route' => 'admin.audit', 'label' => __('Audit log')];
    }
@endphp

<nav class="border-b border-gray-200 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex space-x-6 overflow-x-auto">
            @foreach ($items as $item)
                @php($active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']).'*'))
                <a href="{{ route($item['route']) }}"
                    class="whitespace-nowrap py-4 text-sm font-medium border-b-2 {{ $active ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</nav>
