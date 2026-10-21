<?php

use App\Services\Novedades\SourceAvailabilityChecker;

test('a real reachable source is reported as available', function () {
    config()->set('novedades.source_check.enabled', true);

    $result = (new SourceAvailabilityChecker)->check('https://example.com');

    expect($result['available'])->toBeTrue()
        ->and($result['checked_at'])->not->toBeNull();
})->group('integration');

test('a real unreachable source is reported as unavailable', function () {
    config()->set('novedades.source_check.enabled', true);

    $result = (new SourceAvailabilityChecker)->check('https://this-domain-should-not-exist-'.uniqid().'.invalid');

    expect($result['available'])->toBeFalse();
})->group('integration');
