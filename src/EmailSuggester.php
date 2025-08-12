<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Raxos\Mail\Error\EmailAddressException;
use Raxos\Mail\Util\PublicSuffixList;
use function array_map;
use function explode;
use function is_string;
use function levenshtein;
use function str_replace;

/**
 * Class EmailSuggester
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class EmailSuggester
{

    private const array COMMON_PROVIDERS = ['icloud', 'me', 'hotmail', 'outlook', 'live', 'gmail'];

    /**
     * Returns suggestions for the given email if there are any.
     *
     * @param Email|string $email
     *
     * @return Email[]|null
     * @throws EmailAddressException
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public static function for(Email|string $email): ?array
    {
        PublicSuffixList::load();

        if (is_string($email)) {
            $email = Email::fromString($email);
        }

        [$provider] = explode('.', $email->domain, 2);
        $isKnownSuffix = PublicSuffixList::validateDomain($email->domain, $suffixSuggestions);

        $chosenCommonProvider = null;

        foreach (self::COMMON_PROVIDERS as $commonProvider) {
            if (levenshtein($provider, $commonProvider) === 1) {
                $chosenCommonProvider = $commonProvider;
                break;
            }
        }

        // note: The suffix is unknown, and there's probably a typo in the provider.
        if (!$isKnownSuffix && $chosenCommonProvider !== null) {
            return array_map(function (string $domain) use ($chosenCommonProvider, $email, $provider): Email {
                $domain = str_replace($provider, $chosenCommonProvider, $domain);

                return new Email($email->username, $domain, $email->tag);
            }, $suffixSuggestions);
        }

        // note: There is probably a typo in the provider.
        if ($chosenCommonProvider !== null) {
            $domain = str_replace($provider, $chosenCommonProvider, $email->domain);

            return [
                new Email($email->username, $domain, $email->tag)
            ];
        }

        // note: The suffix is unknown.
        if (!$isKnownSuffix) {
            return array_map(static fn(string $domain) => new Email($email->username, $domain, $email->tag), $suffixSuggestions);
        }

        // note: There seems nothing wrong with the email address.
        return null;
    }

}
