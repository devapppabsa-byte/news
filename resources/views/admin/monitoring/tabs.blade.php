<div class="flex items-center gap-4 border-b border-gray-200">
    <a href="{{ route('admin.monitoring.runs') }}"
        class="py-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.monitoring.runs') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ __('Search runs') }}
    </a>
    <a href="{{ route('admin.monitoring.deliveries') }}"
        class="py-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.monitoring.deliveries') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ __('Email deliveries') }}
    </a>
    <a href="{{ route('admin.monitoring.errors') }}"
        class="py-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.monitoring.errors') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ __('Errors') }}
    </a>
</div>
