<?php
declare(strict_types=1);

namespace Raxos\Mail\Error;

use Exception;
use Raxos\Foundation\Error\{ExceptionId, RaxosException};

/**
 * Class MailerException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail\Error
 * @since 2.0.0
 */
final class MailerException extends RaxosException
{

    /**
     * Returns the exception for when mailing failed.
     *
     * @param Exception $err
     *
     * @return self
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public static function failed(Exception $err): self
    {
        return new self(
            ExceptionId::guess(),
            'mailer_failed',
            $err->getMessage(),
            $err
        );
    }

    /**
     * Returns the exception for when an invalid mail provider is requested.
     *
     * @param string $provider
     *
     * @return self
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public static function invalidProvider(string $provider): self
    {
        return new self(
            ExceptionId::guess(),
            'mailer_failed',
            "Invalid mail provider: {$provider}"
        );
    }

}
