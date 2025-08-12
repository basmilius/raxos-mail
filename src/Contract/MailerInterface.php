<?php
declare(strict_types=1);

namespace Raxos\Mail\Contract;

use Raxos\Mail\Error\MailerException;
use Raxos\Mail\Mail;

/**
 * Interface MailerInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail\Contract
 * @since 2.0.0
 */
interface MailerInterface
{

    /**
     * Sends the given mail.
     *
     * @param Mail $mail
     *
     * @return bool
     * @throws MailerException
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function send(Mail $mail): bool;

}
