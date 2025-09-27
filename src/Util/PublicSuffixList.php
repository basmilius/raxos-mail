<?php
declare(strict_types=1);

namespace Raxos\Mail\Util;

use RuntimeException;
use function array_map;
use function array_pop;
use function array_search;
use function array_slice;
use function array_unshift;
use function explode;
use function fclose;
use function fgets;
use function fopen;
use function implode;
use function in_array;
use function levenshtein;
use function preg_replace;
use function str_ends_with;
use function str_starts_with;
use function trim;
use function usort;

/**
 * Class PublicSuffixList
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail\Util
 * @since 1.0.0
 */
final class PublicSuffixList
{

    private const array PRIORITY_SUFFIXES = ['com'];

    private static array $suffixes = [];

    /**
     * Loads the public suffix list.
     *
     * @param bool $force
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public static function load(bool $force = false): void
    {
        if (!empty(self::$suffixes) && !$force) {
            return;
        }

        $h = fopen(__DIR__ . '/../../public-suffix-list.dat', 'rb');

        if (!$h) {
            throw new RuntimeException('Could not open public suffix file.', 500);
        }

        while (($line = fgets($h)) !== false) {
            $line = trim($line);

            // todo(Bas): for now, we do not support wildcards and exceptions. We'll
            //  support this in a future release.
            if (empty($line) || str_starts_with($line, '//') || str_starts_with($line, '*') || str_starts_with($line, '!')) {
                continue;
            }

            array_unshift(self::$suffixes, $line);
        }

        fclose($h);
    }

    /**
     * Returns suggestions with valid suffixes based on the given domain.
     *
     * @param string $domain
     * @param int $count
     *
     * @return string[]
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public static function findSuggestionsForInvalidDomain(string $domain, int $count = 3): array
    {
        $parts = explode('.', $domain);
        $suffix = array_pop($parts);
        $domain = implode('.', $parts);

        $suffixes = array_map(static fn(string $s) => [$suffix === $s ? -1 : levenshtein($suffix, $s), $s], self::$suffixes);
        usort($suffixes, static fn(array $a, array $b): int => $a[0] <=> $b[0]);

        $suffixes = array_slice($suffixes, 0, $count);
        usort($suffixes, static function (array $a, array $b): int {
            $ap = in_array($a[1], self::PRIORITY_SUFFIXES, true);
            $bp = in_array($b[1], self::PRIORITY_SUFFIXES, true);

            if ($ap && !$bp) {
                return -1;
            }

            if ($bp && !$ap) {
                return 1;
            }

            return array_search($b[1], self::$suffixes, true) <=> array_search($a[1], self::$suffixes, true);
        });

        return array_map(static fn(array $suffix): string => "{$domain}.{$suffix[1]}", $suffixes);
    }

    /**
     * Gets the domain and suffix.
     *
     * @param string $domain
     *
     * @return array|null
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public static function parseDomain(string $domain): ?array
    {
        foreach (self::$suffixes as $tld) {
            if (!str_ends_with($domain, '.' . $tld)) {
                continue;
            }

            return [
                preg_replace("/.{$tld}$/", '', $domain),
                $tld
            ];
        }

        return [$domain, null];
    }

    /**
     * Validates the given domain and provides suggestions when it is not valid.
     *
     * @param string $domain
     * @param string[]|null $suggestions
     *
     * @return bool
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public static function validateDomain(string $domain, ?array &$suggestions = null): bool
    {
        [, $suffix] = self::parseDomain($domain);
        $isValid = $suffix !== null;

        if (!$isValid) {
            $suggestions = self::findSuggestionsForInvalidDomain($domain);
        }

        return $isValid;
    }

}
