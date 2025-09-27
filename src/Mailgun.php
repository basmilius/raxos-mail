<?php
declare(strict_types=1);

namespace Raxos\Mail;

use Mailgun\Mailgun as MailgunClient;
use Mailgun\Message\Exceptions\LimitExceeded;
use Mailgun\Message\MessageBuilder;
use Psr\Http\Client\ClientExceptionInterface;
use Raxos\Contract\Mail\MailerInterface;
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
final readonly class Mailgun implements MailerInterface
{

    private MailgunClient $client;

    /**
     * Mailgun constructor.
     *
     * @param string $apiKey
     * @param string $domain
     * @param string $endpoint
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        #[SensitiveParameter] public string $apiKey,
        #[SensitiveParameter] public string $domain,
        #[SensitiveParameter] public string $endpoint = 'https://api.eu.mailgun.net',
    )
    {
        $this->client = MailgunClient::create($this->apiKey, $this->endpoint);
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function send(Mail $mail): bool
    {
        try {
            $builder = new MessageBuilder();
            $builder->setFromAddress($mail->sender->email, ['full_name' => $mail->sender->name]);

            foreach ($mail->recipients as $recipient) {
                match ($recipient->type) {
                    RecipientType::TO => $builder->addToRecipient($recipient->email, ['full_name' => $recipient->name]),
                    RecipientType::CC => $builder->addCcRecipient($recipient->email, ['full_name' => $recipient->name]),
                    RecipientType::BCC => $builder->addBccRecipient($recipient->email, ['full_name' => $recipient->name])
                };
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

            $this->client->messages()->send($this->domain, $builder->getMessage());

            return true;
        } catch (ClientExceptionInterface|LimitExceeded $err) {
            throw new MailerFailedException($err);
        }
    }

}
