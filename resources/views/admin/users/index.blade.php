<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full"
                            :value="$search" placeholder="{{ __('Name or email') }}" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status"
                            class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ __($option->label()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <select id="role" name="role"
                            class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($roles as $option)
                                <option value="{{ $option->value }}" @selected($role === $option->value)>{{ __($option->label()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Role') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Verified') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Topics') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Registered') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $user->isAdmin() ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ __($user->role->label()) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $user->isSuspended() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                        {{ __($user->status->label()) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->monitored_topics_count }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->created_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 hover:underline">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">{{ __('No users found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
