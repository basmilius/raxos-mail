<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Mailgun\Mailgun as MailgunClient;
use Mailgun\Message\Exceptions\LimitExceeded;
use Mailgun\Message\MessageBuilder;
use Psr\Http\Client\ClientExceptionInterface;
use Raxos\Mail\Error\MailerFailedException;
use SensitiveParameter;
use function Raxos\Foundation\isTesting;

/**
 * Class Mailgun
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Mailgun implements SubmissionMailerInterface
{
    /**
     * Retains the configured transport client without rebuilding it for each request.
     *
     * @var MailgunClient
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    private MailgunClient $client;

    /**
     * Mailgun constructor.
     *
     * @param string $apiKey
     * @param string $domain
     * @param string $endpoint
     * @param MailgunClient|null $client
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        #[SensitiveParameter] public string $apiKey,
        #[SensitiveParameter] public string $domain,
        #[SensitiveParameter] public string $endpoint = 'https://api.eu.mailgun.net',
        ?MailgunClient $client = null,
    )
    {
        $this->client = $client ?? MailgunClient::create($this->apiKey, $this->endpoint);
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function send(Mail $mail): bool
    {
        return $this->sendWithResult($mail)->accepted;
    }

    /**
     * Returns the Mailgun message identity and forwards supported correlation and tracking options.
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
        try {
            $builder = new MessageBuilder();
            $builder->setFromAddress((string)$mail->sender->email, ['full_name' => $mail->sender->name]);

            foreach ($mail->recipients as $recipient) {
                match ($recipient->type) {
                    RecipientType::TO => $builder->addToRecipient((string)$recipient->email, ['full_name' => $recipient->name]),
                    RecipientType::CC => $builder->addCcRecipient((string)$recipient->email, ['full_name' => $recipient->name]),
                    RecipientType::BCC => $builder->addBccRecipient((string)$recipient->email, ['full_name' => $recipient->name])
                };
            }

            if ($mail->replyTo !== null) {
                $builder->setReplyToAddress((string)$mail->replyTo->email, ['full_name' => $mail->replyTo->name]);
            }

            $builder->setSubject($mail->subject);
            $builder->setHtmlBody($mail->html);
            $builder->setTextBody($mail->text);

            foreach ($mail->attachments as $attachment) {
                $builder->addStringAttachment($attachment->content, $attachment->name);
            }

            if (isTesting()) {
                $builder->setTestMode(true);
            }

            $payload = $builder->getMessage();

            foreach ($metadata as $key => $value) {
                $payload['v:' . $key] = (string)$value;
            }

            $payload['o:tracking-opens'] = $trackOpens ? 'yes' : 'no';
            $result = $this->client->messages()->send($this->domain, $payload);

            return new MailSubmission($result->getId() ?: null, null);
        } catch (ClientExceptionInterface|LimitExceeded $err) {
            throw new MailerFailedException($err);
        }
    }
}
