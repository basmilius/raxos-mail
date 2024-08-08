<?php
declare(strict_types=1);

namespace Raxos\Mail;

use JsonSerializable;
use Raxos\Mail\Util\PublicSuffixList;
use Stringable;
use function array_map;
use function explode;
use function levenshtein;
use function str_replace;
use function substr_count;

/**
 * Class EmailAddress
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 1.0.0
 */
readonly class EmailAddress implements JsonSerializable, Stringable
{

    private const array COMMON_PROVIDERS = [
        'hotmail',
        'outlook',
        'gmail',
        'icloud',
        'me'
    ];

    /**
     * EmailAddress constructor.
     *
     * @param string $username
     * @param string $domain
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function __construct(
        public string $username,
        public string $domain
    )
    {
    }

    /**
     * Validates the email-address.
     *
     * @return EmailAddressResult
     * @throws EmailAddressException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public final function validate(): EmailAddressResult
    {
        PublicSuffixList::load();

        [$provider] = explode('.', $this->domain, 2);
        $isKnownSuffix = PublicSuffixList::validateDomain($this->domain, $suffixSuggestions);

        $commonProvider = null;
        foreach (self::COMMON_PROVIDERS as $p) {
            if (levenshtein($p, $provider) === 1) {
                $commonProvider = $p;
                break;
            }
        }

        if ($isKnownSuffix && $commonProvider === null) {
            return new EmailAddressResultSeemsOkay($this);
        }

        $suggestions = [];

        if (!$isKnownSuffix && $commonProvider !== null) {
            $suggestions = array_map(function (string $domain) use ($commonProvider, $provider): EmailAddress {
                $domain = str_replace($provider, $commonProvider, $domain);

                return self::fromString("{$this->username}@{$domain}");
            }, $suffixSuggestions);
        } else if ($commonProvider !== null) {
            $domain = str_replace($provider, $commonProvider, $this->domain);

            $suggestions[] = self::fromString("{$this->username}@{$domain}");
        } else if (!$isKnownSuffix) {
            $suggestions = array_map(fn(string $domain) => "{$this->username}@{$domain}", $suffixSuggestions);
            $suggestions = array_map(self::fromString(...), $suggestions);
        }

        return new EmailAddressResultWithSuggestions($this, $suggestions);
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@glybe.nl>
     * @since 1.0.0
     */
    public final function jsonSerialize(): string
    {
        return (string)$this;
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@glybe.nl>
     * @since 1.0.0
     */
    public final function __toString(): string
    {
        return "{$this->username}@{$this->domain}";
    }

    /**
     * @param string $email
     *
     * @return static
     * @throws EmailAddressException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public static function fromString(string $email): static
    {
        if (substr_count($email, '@') !== 1) {
            throw new EmailAddressException('An email-address should have exactly one at-symbol.', EmailAddressException::ERR_INVALID);
        }

        [$username, $domain] = explode('@', $email);

        return new static($username, $domain);
    }

}
