<?php
declare(strict_types=1);

namespace Raxos\Mail\Error;

use Raxos\Contract\Mail\EmailAddressExceptionInterface;
use Raxos\Error\Exception;

/**
 * Class InvalidEmailAddressException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail\Error
 * @since 2.0.0
 */
final class InvalidEmailAddressException extends Exception implements EmailAddressExceptionInterface
{

    /**
     * InvalidEmailAddressException constructor.
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct()
    {
        parent::__construct(
            'email_address_invalid',
            'Not a valid email address.'
        );
    }

}
