<?php
declare(strict_types=1);

namespace Raxos\Mail\Error;

use Raxos\Contract\Mail\MailerExceptionInterface;
use Raxos\Error\Exception;

/**
 * Class MailerInvalidProviderException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail\Error
 * @since 2.0.0
 */
final class MailerInvalidProviderException extends Exception implements MailerExceptionInterface
{

    /**
     * MailerInvalidProviderException constructor.
     *
     * @param string $provider
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public readonly string $provider
    )
    {
        parent::__construct(
            'mailer_invalid_provider',
            "Provider {$this->provider} is not a valid provider."
        );
    }

}
