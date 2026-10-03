<?php
declare(strict_types=1);

namespace Raxos\Mail;

/**
 * Reports provider acceptance and correlation data; acceptance does not establish inbox delivery.
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 3.2.0
 */
final readonly class MailSubmission
{

    /**
     * Retains provider identifiers without storing the mail envelope or body.
     *
     * @param string|null $messageId Null when the transport does not return a correlation identifier.
     * @param string|null $submittedAt Provider timestamp, when available.
     * @param bool $accepted False only when the transport confirms refusal.
     * @author Bas Milius <bas@mili.us>
     * @since 3.2.0
     */
    public function __construct(public ?string $messageId, public ?string $submittedAt, public bool $accepted = true)
    {
    }

}
