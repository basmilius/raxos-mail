<?php
declare(strict_types=1);

use Mailgun\Api\Message;
use Mailgun\Mailgun as MailgunClient;
use Mailgun\Message\Exceptions\LimitExceeded;
use Mailgun\Model\Message\SendResponse;
use Psr\Http\Client\ClientExceptionInterface;
use Raxos\Mail\Email;
use Raxos\Mail\Error\MailerFailedException;
use Raxos\Mail\Mail;
use Raxos\Mail\Mailgun;
use Raxos\Mail\Recipient;
use Raxos\Mail\RecipientType;
use Raxos\Mail\Sender;

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
    }))->willReturn(SendResponse::create(['id' => '<unit-id>', 'message' => 'Queued.']));
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
        ? new LimitExceeded('limit')
        : new class('offline') extends RuntimeException implements ClientExceptionInterface {};
    $message->method('send')->willThrowException($cause);

    try {
        new Mailgun('unused', 'example.org', client: $client)->send(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), []));
        test()->fail('Provider failures must propagate.');
    } catch (MailerFailedException $error) {
        expect($error->getPrevious())->toBe($cause);
    }
})->with([true, false]);

it('returns the Mailgun message identifier and passes correlation metadata', function (): void {
    $response = SendResponse::create(['id' => '<provider-id>', 'message' => 'Queued.']);
    $message = test()->createMock(Message::class);
    $message->expects(test()->once())->method('send')->with('example.org', test()->callback(static fn(array $payload): bool => $payload['v:delivery_id'] === 'delivery' && $payload['o:tracking-opens'] === 'yes'))->willReturn($response);
    $client = test()->createMock(MailgunClient::class);
    $client->method('messages')->willReturn($message);
    $result = new Mailgun('unused', 'example.org', client: $client)->sendWithResult(new Mail('Subject', 'HTML', 'Text', new Sender('sender@example.org', 'Sender'), []), ['delivery_id' => 'delivery'], true);
    expect($result->messageId)->toBe('<provider-id>')->and($result->accepted)->toBeTrue();
});

it('uses a separate Reply-To and supports a response without a provider identifier', function (): void {
    $message = test()->createMock(Message::class);
    $message->expects(test()->once())->method('send')->with('example.org', test()->callback(static function (array $payload): bool {
        return str_contains(implode(',', (array)$payload['h:reply-to']), 'reply@example.org');
    }))->willReturn(SendResponse::create(['id' => '', 'message' => 'Queued.']));
    $client = test()->createMock(MailgunClient::class);
    $client->method('messages')->willReturn($message);
    $result = new Mailgun('unused', 'example.org', client: $client)->sendWithResult(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [], replyTo: new Sender('reply@example.org', 'Replies')));
    expect($result->accepted)->toBeTrue()->and($result->messageId)->toBeNull();
});
