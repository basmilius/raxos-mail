<?php
declare(strict_types=1);

namespace Raxos\Mail;

use PHPMailer\PHPMailer\{PHPMailer};
use Raxos\Contract\Mail\MailerInterface;
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
final readonly class SMTP implements MailerInterface
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
        public string $hostname = ''
    ) {}

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

        try {
            $mailer = new PHPMailer();
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

            $mailer->setFrom($mail->sender->email, $mail->sender->name);
            $mailer->addReplyTo($mail->sender->email, $mail->sender->name);

            foreach ($mail->recipients as $recipient) {
                match ($recipient->type) {
                    RecipientType::TO => $mailer->addAddress($recipient->email, $recipient->name),
                    RecipientType::CC => $mailer->addCC($recipient->email, $recipient->name),
                    RecipientType::BCC => $mailer->addBCC($recipient->email, $recipient->name)
                };
            }

            $mailer->Subject = $mail->subject;
            $mailer->msgHTML($mail->html);

            foreach ($mail->attachments as $attachment) {
                $mailer->addStringAttachment($attachment->content, $attachment->name);
            }

            return $mailer->send();
        } catch (Throwable $err) {
            throw new MailerFailedException($err);
        }
    }

}
