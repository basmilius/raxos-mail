<?php
declare(strict_types=1);

namespace Raxos\Mail;

/**
 * Class Sender
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Sender extends Recipient
{

    /**
     * Sender constructor.
     *
     * @param string $email
     * @param string $name
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(string $email, string $name)
    {
        parent::__construct($email, $name);
    }

}
