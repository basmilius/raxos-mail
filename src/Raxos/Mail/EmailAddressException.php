<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Raxos\Foundation\Error\{ExceptionId, RaxosException};

/**
 * Class EmailAddressException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 1.0.17
 */
final class EmailAddressException extends RaxosException
{

    /**
     * Returns an invalid exception.
     *
     * @return self
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.17
     */
    public static function invalid(): self
    {
        return new self(
            ExceptionId::for(__METHOD__),
            'email_invalid',
            'An email-address should have exactly one @-symbol.'
        );
    }

}
