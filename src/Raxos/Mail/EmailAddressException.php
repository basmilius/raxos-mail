<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Raxos\Foundation\Error\RaxosException;

/**
 * Class EmailAddressException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 1.0.0
 */
class EmailAddressException extends RaxosException
{

    public const ERR_INVALID = 1;
    public const ERR_INVALID_HOSTNAME = 2;
    public const ERR_INVALID_USERNAME = 4;

}
