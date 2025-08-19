<?php
declare(strict_types=1);

namespace Raxos\Mail;

use JsonSerializable;
use Raxos\Mail\Error\EmailAddressException;
use Stringable;
use function explode;
use function str_contains;
use function substr_count;

/**
 * Class Email
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Email implements JsonSerializable, Stringable
{

    /**
     * Email constructor.
     *
     * @param string $username
     * @param string $domain
     * @param string|null $tag
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public string $username,
        public string $domain,
        public ?string $tag = null
    ) {}

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function jsonSerialize(): string
    {
        return (string)$this;
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __toString(): string
    {
        if ($this->tag === null) {
            return "{$this->username}@{$this->domain}";
        }

        return "{$this->username}+{$this->tag}@{$this->domain}";
    }

    /**
     * Returns an Email instance from a string.
     *
     * @param string $email
     *
     * @return self
     * @throws EmailAddressException
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public static function fromString(string $email): self
    {
        if (substr_count($email, '@') !== 1) {
            throw EmailAddressException::invalid();
        }

        [$local, $domain] = explode('@', $email);

        if (str_contains($local, '+')) {
            [$username, $tag] = explode('+', $local);

            return new self($username, $domain, $tag);
        }

        return new self($local, $domain);
    }

}
