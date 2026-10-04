<?php
declare(strict_types=1);

namespace Raxos\Mail;

/**
 * Class MailSubmission
 *
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
     * @param string|null $messageId
     * @param string|null $submittedAt
     * @param bool $accepted
     *
     * @author Bas Milius <bas@mili.us>
     * @since 3.2.0
     */
    public function __construct(
        public ?string $messageId,
        public ?string $submittedAt,
        public bool $accepted = true
    ) {}

}
