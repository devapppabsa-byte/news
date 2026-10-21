<x-mail::message>
# {{ __('Test email from :app', ['app' => $appName]) }}

{{ __('If you are reading this, the outgoing mail configuration works.') }}

{{ __('Sent at :time', ['time' => $sentAt]) }}

{{ __('You can safely ignore this message.') }}
</x-mail::message>
