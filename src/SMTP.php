<?php
declare(strict_types=1);

namespace Raxos\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Raxos\Mail\Error\MailerFailedException;
use SensitiveParameter;
use Throwable;
use function Raxos\Foundation\isTesting;

/**
 * Class SMTP
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class SMTP implements SubmissionMailerInterface
{
    /**
     * SMTP constructor.
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $helo
     * @param string $hostname
     * @param PHPMailer|null $mailer
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        #[SensitiveParameter] public string $host,
        #[SensitiveParameter] public int $port = 587,
        #[SensitiveParameter] public string $username = '',
        #[SensitiveParameter] public string $password = '',
        public string $helo = '',
        public string $hostname = '',
        private ?PHPMailer $mailer = null
    ) {}

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
     * Returns SMTP acceptance and the generated Message-ID; tracking options are unsupported.
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

        try {
            $mailer = $this->mailer ?? new PHPMailer();
            $mailer->clearAllRecipients();
            $mailer->clearReplyTos();
            $mailer->clearAttachments();
            $mailer->isHTML();
            $mailer->isSMTP();
            $mailer->SMTPAuth = true;
            $mailer->SMTPDebug = false;
            $mailer->XMailer = null;
            $mailer->Host = $this->host;
            $mailer->Port = $this->port;
            $mailer->Username = $this->username;
            $mailer->Password = $this->password;

            $mailer->Helo = $this->helo;
            $mailer->Hostname = $this->hostname;
            $mailer->Priority = 3;

            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Encoding = PHPMailer::ENCODING_BASE64;

            $mailer->setFrom((string)$mail->sender->email, $mail->sender->name);
            $replyTo = $mail->replyTo ?? $mail->sender;
            $mailer->addReplyTo((string)$replyTo->email, $replyTo->name);

            foreach ($mail->recipients as $recipient) {
                match ($recipient->type) {
                    RecipientType::TO => $mailer->addAddress((string)$recipient->email, $recipient->name),
                    RecipientType::CC => $mailer->addCC((string)$recipient->email, $recipient->name),
                    RecipientType::BCC => $mailer->addBCC((string)$recipient->email, $recipient->name)
                };
            }

            $mailer->Subject = $mail->subject;
            $mailer->msgHTML($mail->html);
            $mailer->AltBody = $mail->text;

            foreach ($mail->attachments as $attachment) {
                $mailer->addStringAttachment($attachment->content, $attachment->name);
            }

            $accepted = $mailer->send();

            return new MailSubmission($accepted ? ($mailer->getLastMessageID() ?: null) : null, null, $accepted);
        } catch (Throwable $err) {
            throw new MailerFailedException($err);
        }
    }
}
