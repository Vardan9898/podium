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

it('derives the same key for names differing only in case or spacing', function (): void {
    expect(Tag::keyFor('Vue JS'))
        ->toBe(Tag::keyFor('  vue   js '))
        ->toBe(Tag::keyFor('VUE JS'))
        ->toBe('vue js');
});

it('keeps names distinct when they differ by more than case', function (): void {
    expect(array_unique(array_map(Tag::keyFor(...), ['C', 'C#', 'C++', 'Vue.js', 'Vuejs', '日本語'])))->toHaveCount(6)
        ->and(Tag::keyFor('ÉCOLE'))->toBe('école');
});
