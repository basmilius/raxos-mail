<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use Postmark\PostmarkClient;
use Raxos\Mail\{Attachment, Email, Mail, Postmark, Recipient, RecipientType, Sender, SMTP};

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
    } catch (Raxos\Mail\Error\MailerFailedException $error) {
        expect($error->getPrevious())->toBe($failure);
    }
});

it('passes Postmark the correct sender and recipient fields', function (): void {
    $client = $this->createMock(PostmarkClient::class);
    $client->expects($this->once())->method('sendEmail')->with(
        'Sender <sender@example.org>', ['To <to@example.org>'], 'subject', 'html', 'text', null, false,
        'Sender <sender@example.org>', null, null
    );
    $mail = new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [new Recipient('to@example.org', 'To')]);
    expect(new Postmark('unused', $client)->send($mail))->toBeTrue();
});
