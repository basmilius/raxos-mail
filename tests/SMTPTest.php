<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use Raxos\Mail\Attachment;
use Raxos\Mail\Email;
use Raxos\Mail\Error\MailerFailedException;
use Raxos\Mail\Mail;
use Raxos\Mail\Recipient;
use Raxos\Mail\RecipientType;
use Raxos\Mail\Sender;
use Raxos\Mail\SMTP;

covers(SMTP::class);

it('passes Email objects, recipients and attachment bytes to SMTP as strings', function (): void {
    $mailer = $this->createMock(PHPMailer::class);
    $mailer->expects($this->once())->method('setFrom')->with('sender@example.org', 'Sender')->willReturn(true);
    $mailer->expects($this->once())->method('addReplyTo')->with('sender@example.org', 'Sender')->willReturn(true);
    $mailer->expects($this->once())->method('addAddress')->with('to@example.org', 'To')->willReturn(true);
    $mailer->expects($this->once())->method('addCC')->with('cc@example.org', 'Cc')->willReturn(true);
    $mailer->expects($this->once())->method('addBCC')->with('bcc@example.org', 'Bcc')->willReturn(true);
    $mailer->expects($this->once())->method('addStringAttachment')->with('bytes', 'file.txt')->willReturn(true);
    $mailer->expects($this->once())->method('send')->willReturn(true);
    $mail = new Mail('subject', '<b>body</b>', 'body', new Sender(Email::fromString('sender@example.org'), 'Sender'), [
        new Recipient(Email::fromString('to@example.org'), 'To'),
        new Recipient(Email::fromString('cc@example.org'), 'Cc', RecipientType::CC),
        new Recipient(Email::fromString('bcc@example.org'), 'Bcc', RecipientType::BCC),
    ], [new Attachment('file.txt', 'bytes')]);
    expect(new SMTP('unused', mailer: $mailer)->send($mail))->toBeTrue();
});

it('converts SMTP failures to a Raxos error with the original cause', function (): void {
    $mailer = $this->createMock(PHPMailer::class);
    $failure = new RuntimeException('transport failure');
    $mailer->method('send')->willThrowException($failure);
    $mail = new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [new Recipient('to@example.org', 'To')]);

    try {
        new SMTP('unused', mailer: $mailer)->send($mail);
        test()->fail('Expected transport failure.');
    } catch (MailerFailedException $error) {
        expect($error->getPrevious())->toBe($failure);
    }
});

it('configures transport metadata and retains the supplied plaintext alternative', function (): void {
    $mailer = test()->createPartialMock(PHPMailer::class, ['send']);
    $mailer->expects(test()->once())->method('send')->willReturn(true);
    $mail = new Mail('Subject é', '<b>HTML</b>', 'Custom plaintext', new Sender('sender@example.org', 'Sender'), [new Recipient('to@example.org', 'To')]);
    expect(new SMTP('smtp.example.org', 2525, 'user', 'password', 'helo.example.org', 'host.example.org', $mailer)->send($mail))->toBeTrue()
        ->and($mailer->Host)->toBe('smtp.example.org')->and($mailer->Port)->toBe(2525)->and($mailer->SMTPAuth)->toBeTrue()
        ->and($mailer->Username)->toBe('user')->and($mailer->Password)->toBe('password')->and($mailer->Helo)->toBe('helo.example.org')
        ->and($mailer->Hostname)->toBe('host.example.org')->and($mailer->Subject)->toBe('Subject é')->and($mailer->Body)->toBe('<b>HTML</b>')
        ->and($mailer->AltBody)->toBe('Custom plaintext')->and($mailer->CharSet)->toBe(PHPMailer::CHARSET_UTF8);
});

it('does not carry recipients, reply addresses or attachments into the next message', function (): void {
    $mailer = test()->createPartialMock(PHPMailer::class, ['send']);
    $mailer->expects(test()->exactly(2))->method('send')->willReturn(true);
    $provider = new SMTP('unused', mailer: $mailer);
    $provider->send(new Mail('first', 'html', 'text', new Sender('first@example.org', 'First'), [new Recipient('first-to@example.org', 'First To')], [new Attachment('first.txt', 'bytes')]));
    $provider->send(new Mail('second', 'html', 'text', new Sender('second@example.org', 'Second'), [new Recipient('second-to@example.org', 'Second To')]));
    expect($mailer->getToAddresses())->toBe([['second-to@example.org', 'Second To']])->and($mailer->getReplyToAddresses())->toBe([['second@example.org', 'Second']])
        ->and($mailer->getAttachments())->toBe([]);
});

it('returns the transport result without attempting to send in testing mode', function (): void {
    $old = getenv('TESTING');
    putenv('TESTING=true');

    try {
        $mailer = test()->createMock(PHPMailer::class);
        $mailer->expects(test()->never())->method('send');
        expect(new SMTP('unused', mailer: $mailer)->send(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [])))->toBeTrue();
    } finally {
        putenv($old === false ? 'TESTING' : 'TESTING=' . $old);
    }
});

it('returns the SMTP acceptance and message identifier with a separate Reply-To', function (bool $accepted): void {
    $mailer = test()->createPartialMock(PHPMailer::class, ['send', 'getLastMessageID']);
    $mailer->method('send')->willReturn($accepted);
    $mailer->method('getLastMessageID')->willReturn('<smtp-unit>');
    $result = new SMTP('unused', mailer: $mailer)->sendWithResult(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [], replyTo: new Sender('reply@example.org', 'Replies')));
    expect($result->accepted)->toBe($accepted)->and($result->messageId)->toBe($accepted ? '<smtp-unit>' : null)
        ->and($mailer->getReplyToAddresses())->toBe([['reply@example.org', 'Replies']]);
})->with([true, false]);
