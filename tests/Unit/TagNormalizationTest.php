<?php

declare(strict_types=1);

use App\Models\Tag;

it('normalizes display names', function (string $input, string $expected): void {
    expect(Tag::normalizeName($input))->toBe($expected);
})->with([
    'trims' => ['  Laravel  ', 'Laravel'],
    'collapses inner whitespace' => ["Machine \t  Learning", 'Machine Learning'],
    'keeps casing' => ['DevOps', 'DevOps'],
]);

it('derives the same slug for names differing only in case, spacing or accents', function (): void {
    expect(Tag::slugFor('Vue JS'))
        ->toBe(Tag::slugFor('  vue   js '))
        ->toBe(Tag::slugFor('VUE JS'))
        ->toBe('vue-js')
        ->and(Tag::slugFor('Café'))->toBe(Tag::slugFor('cafe'));
});
