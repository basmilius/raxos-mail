<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Postmark\Models\PostmarkAttachment;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Raxos\Mail\Error\MailerFailedException;
use SensitiveParameter;
use function array_filter;
use function array_map;
use function Raxos\Foundation\isTesting;
use function strval;

/**
 * Class Postmark
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Postmark implements SubmissionMailerInterface
{
    /**
     * Retains the configured transport client without rebuilding it for each request.
     *
     * @var PostmarkClient
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    private PostmarkClient $client;

    /**
     * Postmark constructor.
     *
     * @param string $apiKey
     * @param PostmarkClient|null $client
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        #[SensitiveParameter] public string $apiKey,
        ?PostmarkClient $client = null
    )
    {
        $this->client = $client ?? new PostmarkClient($this->apiKey);
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function send(Mail $mail): bool
    {
        $this->sendWithResult($mail);

        return true;
    }

    /**
     * Returns the Postmark message identity and forwards metadata for webhook correlation.
     *
     * @param Mail $mail
     * @param array<string, scalar> $metadata
     * @param bool $trackOpens
     *
     * @return MailSubmission
     * @throws MailerFailedException
     * @author Bas Milius <bas@mili.us>
     * @since 3.2.0
     */
    public function sendWithResult(
        Mail $mail,
        array $metadata = [],
        bool $trackOpens = false
    ): MailSubmission
    {
        if (isTesting()) {
            return new MailSubmission(null, null);
        }

        $to = array_map(strval(...), array_filter($mail->recipients, static fn(Recipient $recipient) => $recipient->type === RecipientType::TO));
        $cc = array_map(strval(...), array_filter($mail->recipients, static fn(Recipient $recipient) => $recipient->type === RecipientType::CC));
        $bcc = array_map(strval(...), array_filter($mail->recipients, static fn(Recipient $recipient) => $recipient->type === RecipientType::BCC));

        $attachments = array_map(static fn(Attachment $attachment) => PostmarkAttachment::fromRawData(
            $attachment->content,
            $attachment->name
        ), $mail->attachments);

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
            $response = $this->client->sendEmail(
                from: (string)$mail->sender,
                to: $to,
                subject: $mail->subject,
                htmlBody: $mail->html,
                textBody: $mail->text,
                trackOpens: $trackOpens,
                replyTo: (string)($mail->replyTo ?? $mail->sender),
                cc: $cc,
                bcc: $bcc,
                attachments: $attachments,
                metadata: $metadata !== [] ? $metadata : null,
                messageStream: 'outbound'
            );

            if ($response->ErrorCode !== 0) {
                throw new PostmarkException('Postmark rejected the message.', $response->ErrorCode);
            }

            return new MailSubmission($response->MessageID ?: null, $response->SubmittedAt ?: null);
        } catch (PostmarkException $err) {
            throw new MailerFailedException($err);
        }
    }
}
