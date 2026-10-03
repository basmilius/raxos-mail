<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Raxos\Contract\Mail\{MailerExceptionInterface, MailerInterface};

/**
 * Interface SubmissionMailerInterface
 *
 * Extends boolean sending with provider correlation and optional recipient activity tracking.
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 3.2.0
 */
interface SubmissionMailerInterface extends MailerInterface
{

    /**
     * Returns submission metadata without treating provider acceptance as recipient delivery.
     *
     * @param Mail $mail
     * @param array<string, scalar> $metadata Provider correlation data; unsupported transports may ignore it.
     * @param bool $trackOpens Requests tracking only when the transport supports it.
     *
     * @return MailSubmission
     * @throws MailerExceptionInterface
     * @author Bas Milius <bas@mili.us>
     * @since 3.2.0
     */
    public function sendWithResult(Mail $mail, array $metadata = [], bool $trackOpens = false): MailSubmission;

}
