<?php
declare(strict_types=1);

use Mailgun\Api\Message;
use Mailgun\Mailgun as MailgunClient;
use Psr\Http\Client\ClientExceptionInterface;
use Raxos\Mail\{Email, Mail, Mailgun, Recipient, RecipientType, Sender};

covers(Mailgun::class);

it('sends Email value objects through the Mailgun string interface', function (): void {
    $message = $this->createMock(Message::class);
    $client = $this->createMock(MailgunClient::class);
    $client->method('messages')->willReturn($message);
    $message->expects($this->once())->method('send')->with('example.org', $this->callback(static function (array $payload): bool {
        return str_contains(implode(',', $payload['from']), 'sender@example.org')
            && str_contains(implode(',', $payload['to']), 'to@example.org')
            && str_contains(implode(',', $payload['cc']), 'cc@example.org')
            && str_contains(implode(',', $payload['bcc']), 'bcc@example.org');
    }));
    $mail = new Mail('Subject', '<b>Body</b>', 'Body', new Sender(new Email('sender', 'example.org'), 'Sender'), [
        new Recipient(new Email('to', 'example.org'), 'To'),
        new Recipient(new Email('cc', 'example.org'), 'Cc', RecipientType::CC),
        new Recipient(new Email('bcc', 'example.org'), 'Bcc', RecipientType::BCC),
    ]);
    expect(new Mailgun('test-key', 'example.org', client: $client)->send($mail))->toBeTrue();
});

it('wraps provider request and capacity failures with their cause', function (bool $limit): void {
    $message = test()->createMock(Message::class);
    $client = test()->createMock(MailgunClient::class);
    $client->method('messages')->willReturn($message);
    $cause = $limit
        ? new \Mailgun\Message\Exceptions\LimitExceeded('limit')
        : new class('offline') extends RuntimeException implements ClientExceptionInterface
        {
        };
    $message->method('send')->willThrowException($cause);
    try {
        new Mailgun('unused', 'example.org', client: $client)->send(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), []));
        test()->fail('Provider failures must propagate.');
    } catch (Raxos\Mail\Error\MailerFailedException $error) {
        expect($error->getPrevious())->toBe($cause);
    }
})->with([true, false]);
