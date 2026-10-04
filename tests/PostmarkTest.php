<?php
declare(strict_types=1);

use Postmark\Models\PostmarkException;
use Postmark\Models\PostmarkResponse;
use Postmark\PostmarkClient;
use Raxos\Mail\Attachment;
use Raxos\Mail\Error\MailerFailedException;
use Raxos\Mail\Mail;
use Raxos\Mail\Postmark;
use Raxos\Mail\Recipient;
use Raxos\Mail\RecipientType;
use Raxos\Mail\Sender;

covers(Postmark::class);

it('passes Postmark the correct sender and recipient fields', function (): void {
    $client = $this->createMock(PostmarkClient::class);
    $client->expects($this->once())->method('sendEmail')->with(
        'Sender <sender@example.org>',
        ['To <to@example.org>'],
        'subject',
        'html',
        'text',
        null,
        false,
        'Sender <sender@example.org>',
        null,
        null
    )->willReturn(new PostmarkResponse(['MessageID' => 'test', 'SubmittedAt' => '2026-10-04T00:00:00Z']));
    $mail = new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [new Recipient('to@example.org', 'To')]);
    expect(new Postmark('unused', $client)->send($mail))->toBeTrue();
});

it('maps CC, BCC and attachment bytes into Postmark fields', function (): void {
    $client = test()->createMock(PostmarkClient::class);
    $client->expects(test()->once())->method('sendEmail')->with(
        'Sender <sender@example.org>',
        [],
        'subject',
        'html',
        'text',
        null,
        false,
        'Sender <sender@example.org>',
        ['Cc <cc@example.org>'],
        [1 => 'Bcc <bcc@example.org>'],
        null,
        test()->callback(static fn(array $files): bool => count($files) === 1 && $files[0]->jsonSerialize()['Content'] === base64_encode('bytes') && $files[0]->jsonSerialize()['Name'] === 'unit.txt'),
    )->willReturn(new PostmarkResponse(['MessageID' => 'test', 'SubmittedAt' => '2026-10-04T00:00:00Z']));
    $mail = new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [new Recipient('cc@example.org', 'Cc', RecipientType::CC), new Recipient('bcc@example.org', 'Bcc', RecipientType::BCC)], [new Attachment('unit.txt', 'bytes')]);
    expect(new Postmark('unused', $client)->send($mail))->toBeTrue();
});

it('wraps Postmark API failures with the original cause', function (): void {
    $client = test()->createMock(PostmarkClient::class);
    $cause = new PostmarkException('offline');
    $client->method('sendEmail')->willThrowException($cause);

    try {
        new Postmark('unused', $client)->send(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), []));
        test()->fail('Provider failures must propagate.');
    } catch (MailerFailedException $error) {
        expect($error->getPrevious())->toBe($cause);
    }
});

it('does not invoke the API client in testing mode', function (): void {
    $old = getenv('TESTING');
    putenv('TESTING=true');

    try {
        $client = test()->createMock(PostmarkClient::class);
        $client->expects(test()->never())->method('sendEmail');
        expect(new Postmark('unused', $client)->send(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [])))->toBeTrue();
    } finally {
        putenv($old === false ? 'TESTING' : 'TESTING=' . $old);
    }
});


it('returns the provider identity and forwards delivery metadata and open tracking', function (): void {
    $client = test()->createMock(PostmarkClient::class);
    $client->expects(test()->once())->method('sendEmail')->with(
        test()->anything(), test()->anything(), test()->anything(), test()->anything(), test()->anything(),
        null, true, test()->anything(), null, null, null, null, null, ['delivery_id' => 'delivery'], 'outbound'
    )->willReturn(new PostmarkResponse(['MessageID' => 'provider-id', 'SubmittedAt' => '2026-10-04T12:00:00Z']));
    $result = new Postmark('unused', $client)->sendWithResult(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), []), ['delivery_id' => 'delivery'], true);
    expect($result->messageId)->toBe('provider-id')->and($result->submittedAt)->toBe('2026-10-04T12:00:00Z');
});

it('passes a separate Reply-To and rejects provider errors instead of returning success', function (bool $rejected): void {
    $client = test()->createMock(PostmarkClient::class);
    $client->method('sendEmail')->willReturnCallback(static function (...$args) use ($rejected): PostmarkResponse {
        expect($args[7])->toBe('Replies <reply@example.org>');

        return new PostmarkResponse(['ErrorCode' => $rejected ? 300 : 0, 'MessageID' => '', 'SubmittedAt' => '']);
    });
    $provider = new Postmark('unused', $client);
    $mail = new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [], replyTo: new Sender('reply@example.org', 'Replies'));

    if ($rejected) {
        expect(fn() => $provider->sendWithResult($mail))->toThrow(MailerFailedException::class);
    } else {
        expect($provider->sendWithResult($mail)->messageId)->toBeNull();
    }
})->with([true, false]);
