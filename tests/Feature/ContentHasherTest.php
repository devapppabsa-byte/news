<?php

use App\Support\ContentHasher;

test('the same content with different formatting yields the same hash', function () {
    $hasher = new ContentHasher;

    $a = $hasher->hash('Breaking: The market rallied!', 'Stocks rose 3 percent.');
    $b = $hasher->hash('  breaking the market rallied  ', 'stocks rose 3 percent');

    expect($a)->toBe($b);
});

test('different content yields different hashes', function () {
    $hasher = new ContentHasher;

    expect($hasher->hash('One', 'Alpha'))->not->toBe($hasher->hash('Two', 'Beta'));
});

test('missing snippets are handled', function () {
    $hasher = new ContentHasher;

    expect($hasher->hash('Title'))->toBe($hasher->hash('Title', null));
});
