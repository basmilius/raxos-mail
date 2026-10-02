<?php
declare(strict_types=1);

use Postmark\PostmarkClient;
use Raxos\Mail\{Attachment, Mail, Postmark, Recipient, RecipientType, Sender};

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
    );
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
        test()->callback(static fn (array $files): bool => count($files) === 1 && $files[0]->jsonSerialize()['Content'] === base64_encode('bytes') && $files[0]->jsonSerialize()['Name'] === 'unit.txt'),
    );
    $mail = new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), [new Recipient('cc@example.org', 'Cc', RecipientType::CC), new Recipient('bcc@example.org', 'Bcc', RecipientType::BCC)], [new Attachment('unit.txt', 'bytes')]);
    expect(new Postmark('unused', $client)->send($mail))->toBeTrue();
});

it('wraps Postmark API failures with the original cause', function (): void {
    $client = test()->createMock(PostmarkClient::class);
    $cause = new \Postmark\Models\PostmarkException('offline');
    $client->method('sendEmail')->willThrowException($cause);
    try {
        new Postmark('unused', $client)->send(new Mail('subject', 'html', 'text', new Sender('sender@example.org', 'Sender'), []));
        test()->fail('Provider failures must propagate.');
    } catch (Raxos\Mail\Error\MailerFailedException $error) {
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
