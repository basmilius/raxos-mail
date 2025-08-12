<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Stringable;

/**
 * Class Recipient
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
readonly class Recipient implements Stringable
{

    /**
     * Recipient constructor.
     *
     * @param Email|string $email
     * @param string $name
     * @param RecipientType $type
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public Email|string $email,
        public string $name,
        public RecipientType $type = RecipientType::TO
    ) {}

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __toString(): string
    {
        return "{$this->name} <{$this->email}>";
    }

}
