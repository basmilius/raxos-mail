<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Postmark\Models\{PostmarkAttachment, PostmarkException};
use Postmark\PostmarkClient;
use Raxos\Mail\Contract\MailerInterface;
use Raxos\Mail\Error\MailerException;
use SensitiveParameter;
use function array_filter;
use function array_map;
use function Raxos\Foundation\isTesting;

/**
 * Class Postmark
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Postmark implements MailerInterface
{

    private PostmarkClient $client;

    /**
     * Postmark constructor.
     *
     * @param string $apiKey
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        #[SensitiveParameter] public string $apiKey
    )
    {
        $this->client = new PostmarkClient($this->apiKey);
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function send(Mail $mail): bool
    {
        if (isTesting()) {
            return true;
        }

        $to = array_map(\strval(...), array_filter($mail->recipients, static fn(Recipient $recipient) => $recipient->type === RecipientType::TO));
        $cc = array_map(\strval(...), array_filter($mail->recipients, static fn(Recipient $recipient) => $recipient->type === RecipientType::CC));
        $bcc = array_map(\strval(...), array_filter($mail->recipients, static fn(Recipient $recipient) => $recipient->type === RecipientType::BCC));

        $attachments = array_map(static fn(Attachment $attachment) => PostmarkAttachment::fromRawData(
            $attachment->content,
            $attachment->name
        ), $mail->attachments);

        if (empty($to)) {
            $to = null;
        }

        if (empty($cc)) {
            $cc = null;
        }

        if (empty($bcc)) {
            $bcc = null;
        }

        if (empty($attachments)) {
            $attachments = null;
        }

        try {
            $this->client->sendEmail(
                (string)$mail->sender,
                $to,
                $mail->subject,
                $mail->html,
                $mail->text,
                null,
                false,
                (string)$mail->sender,
                $cc,
                $bcc,
                null,
                $attachments,
                null,
                null,
                'outbound'
            );

            return true;
        } catch (PostmarkException $err) {
            throw MailerException::failed($err);
        }
    }

}
