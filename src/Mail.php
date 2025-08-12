<?php
declare(strict_types=1);

namespace Raxos\Mail;

/**
 * Class Mail
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Mail
{

    /**
     * Mail constructor.
     *
     * @param string $subject
     * @param string $html
     * @param string $text
     * @param Sender $sender
     * @param Recipient[] $recipients
     * @param Attachment[] $attachments
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public string $subject,
        public string $html,
        public string $text,
        public Sender $sender,
        public array $recipients,
        public array $attachments = []
    ) {}

}
