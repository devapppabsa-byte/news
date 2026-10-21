<x-mail::message>
# {{ __('New novedad in ":topic"', ['topic' => $topicName]) }}

**{{ $title }}**

@if (! empty($source))
{{ __('Source') }}: {{ $source }}
@endif

@if (! empty($relevance))
{{ __('Relevance') }}: {{ $relevance }}/100
@endif

@if (! empty($summary))
{{ $summary }}
@endif

<x-mail::button :url="$url">
{{ __('Read the full story') }}
</x-mail::button>

{{ __('You receive this alert because you enabled notifications for this topic.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
