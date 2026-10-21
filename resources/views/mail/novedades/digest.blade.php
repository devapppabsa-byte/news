<x-mail::message>
# {{ __('Your novedades summary') }}

{{ __('Hello :name,', ['name' => $userName]) }}

{{ __('Here are the :count novedades we found for you during :period.', ['count' => $count, 'period' => $period]) }}

@forelse ($items as $item)
## [{{ $item['title'] }}]({{ $item['url'] }})

{{ $item['topic'] }}@if (! empty($item['source'])) · {{ $item['source'] }}@endif

@if (! empty($item['summary']))
{{ \Illuminate\Support\Str::limit($item['summary'], 240) }}
@endif

@endforelse

{{ __('You receive this summary based on your notification preferences.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
