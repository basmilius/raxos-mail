<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Stringable;

/**
 * Class Sender
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Sender implements Stringable
{

    /**
     * Sender constructor.
     *
     * @param Email|string $email
     * @param string $name
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public Email|string $email,
        public string $name
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
