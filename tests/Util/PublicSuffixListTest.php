<?php
declare(strict_types=1);

use Raxos\Mail\Util\PublicSuffixList;

covers(PublicSuffixList::class);

it('replaces the public suffix index on forced reload', function (): void {
    PublicSuffixList::load(true);
    $before = PublicSuffixList::findSuggestionsForInvalidDomain('example.cmo', 5);
    PublicSuffixList::load(true);
    expect(PublicSuffixList::findSuggestionsForInvalidDomain('example.cmo', 5))->toBe($before);
    expect(count(array_unique($before)))->toBe(5);
    expect(PublicSuffixList::parseDomain('example.co.uk'))->toBe(['example', 'co.uk']);
});

it('identifies supported multi-label domains and rejects unknown suffixes', function (string $domain, bool $valid): void {
    expect(PublicSuffixList::validateDomain($domain))->toBe($valid);
})->with([['example.org', true], ['example.co.uk', true], ['sub.example.com', true], ['example.invalidsuffix', false]]);

it('accepts DNS suffixes case insensitively and preserves the domains mailbox-facing portion', function (): void {
    PublicSuffixList::load();
    expect(PublicSuffixList::validateDomain('EXAMPLE.ORG'))->toBeTrue()
        ->and(PublicSuffixList::parseDomain('Sub.Example.CO.UK'))->toBe(['Sub.Example', 'co.uk']);
});

it('returns limited, unique suggestions only for unknown suffixes', function (): void {
    PublicSuffixList::load();
    $suggestions = null;
    expect(PublicSuffixList::validateDomain('example.invalidsuffix', $suggestions))->toBeFalse()->and($suggestions)->toHaveCount(3)
        ->and(PublicSuffixList::findSuggestionsForInvalidDomain('example.cmo', 0))->toBe([])
        ->and(PublicSuffixList::parseDomain('localhost'))->toBe(['localhost', null]);
});
