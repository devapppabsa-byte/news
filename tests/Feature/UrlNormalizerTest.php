<?php

use App\Support\UrlNormalizer;

test('it strips tracking parameters and normalizes the url', function () {
    $normalizer = new UrlNormalizer;

    $normalized = $normalizer->normalize('https://WWW.Example.com/Article/?utm_source=news&utm_medium=email&id=42#section');

    expect($normalized)->toBe('https://example.com/Article?id=42');
});

test('it treats the same url with different tracking as equal', function () {
    $normalizer = new UrlNormalizer;

    $a = $normalizer->hash('https://example.com/a?utm_source=x');
    $b = $normalizer->hash('https://www.example.com/a/');

    expect($a)->toBe($b);
});

test('it keeps meaningful query parameters sorted', function () {
    $normalizer = new UrlNormalizer;

    expect($normalizer->normalize('https://example.com/x?b=2&a=1'))
        ->toBe('https://example.com/x?a=1&b=2');
});

test('it normalizes empty and unstructured input safely', function () {
    $normalizer = new UrlNormalizer;

    expect($normalizer->normalize(''))->toBe('')
        ->and($normalizer->normalize('not a url'))->toBe('not a url');
});
