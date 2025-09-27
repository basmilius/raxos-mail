<?php
declare(strict_types=1);

namespace Raxos\Mail\Error;

use Raxos\Contract\Mail\MailerExceptionInterface;
use Raxos\Error\Exception;
use Throwable;

/**
 * Class MailerFailedException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail\Error
 * @since 2.0.0
 */
final class MailerFailedException extends Exception implements MailerExceptionInterface
{

    /**
     * MailerFailedException constructor.
     *
     * @param Throwable $err
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public readonly Throwable $err
    )
    {
        parent::__construct(
            'mailer_failed',
            $err->getMessage(),
            previous: $this->err
        );
    }

}
